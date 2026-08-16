<?php

use App\Livewire\DynamicForm\Form;
use App\Models\User;
use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\RelationListField;
use App\Support\DynamicForm\Core\Fields\TextField;
use App\Support\DynamicForm\Core\FormDefinitionRegistry;
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Modules\General\Models\World\Companies\Company;
use Modules\General\System\Application;
use Nnjeim\World\Models\City;
use Nnjeim\World\Models\Country;

/**
 * Covers the single-level addRelationList() picker on its own terms.
 * CompanyForm/PersonForm now use the Country|State|City cascade for their
 * city field, so this uses a dedicated fixture definition rather than
 * riding on a real Application's form.
 */
class RelationListFixtureForm extends DynamicForm
{
    public function model(): string
    {
        return Company::class;
    }

    public function fields(): array
    {
        return [
            TextField::make('name')->label('Name')->required(),
            RelationListField::make('city')
                ->model(City::class)
                ->field('name')
                ->label('City'),
        ];
    }
}

/** Same, but only cities of a country whose iso2 is 'EG' may be picked. */
class ScopedRelationListFixtureForm extends RelationListFixtureForm
{
    public function fields(): array
    {
        return [
            TextField::make('name')->label('Name')->required(),
            RelationListField::make('city')
                ->model(City::class)
                ->field('name')
                ->label('City')
                ->query(fn ($query) => $query->where('country_code', 'EG')),
        ];
    }
}

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());

    app(FormDefinitionRegistry::class)->register('test.relation-list', RelationListFixtureForm::class);

    $this->country = Country::create(['name' => 'Egypt', 'iso2' => 'EG', 'iso3' => 'EGY', 'phone_code' => '20', 'region' => 'Africa', 'subregion' => 'Northern Africa', 'status' => 1]);
});

function makeListCity(string $name): City
{
    return City::create([
        'name' => $name,
        'country_id' => test()->country->id,
        'state_id' => 0,
        'country_code' => 'EG',
    ]);
}

function relationListForm(): Testable
{
    return Livewire::test(Form::class, ['formKey' => 'test.relation-list']);
}

/** See CascadingCityPickerTest for why the compiled attribute is asserted. */
it('compiles a callable wire:click onto the trigger button', function (): void {
    $html = relationListForm()->html();

    expect($html)->toContain("toggleRelationPicker('city')")
        ->and($html)->not->toContain('@js(');
});

it('renders the picker panel only after the trigger is clicked', function (): void {
    makeListCity('Cairo');

    $component = relationListForm();

    expect($component->get('activeRelationField'))->toBe('');
    $component->assertDontSee('Cairo');

    $component->call('toggleRelationPicker', 'city');

    expect($component->get('activeRelationField'))->toBe('city');
    $component->assertSee('Cairo');

    $component->call('toggleRelationPicker', 'city');
    expect($component->get('activeRelationField'))->toBe('');
});

it('loads the first page, reports more, and loads the rest on demand', function (): void {
    foreach (['Alexandria', 'Aswan', 'Asyut', 'Cairo', 'Giza', 'Luxor', 'Suez'] as $name) {
        makeListCity($name);
    }

    $component = relationListForm()->call('openRelationPicker', 'city');

    // pageSize is 5, so 7 cities must not arrive at once.
    expect($component->get('relationResults.city'))->toHaveCount(5)
        ->and($component->get('relationHasMore.city'))->toBeTrue();

    $component->call('loadMoreRelation', 'city');

    expect($component->get('relationResults.city'))->toHaveCount(7)
        ->and($component->get('relationHasMore.city'))->toBeFalse();
});

it('narrows candidates by search', function (): void {
    foreach (['Alexandria', 'Aswan', 'Asyut', 'Cairo'] as $name) {
        makeListCity($name);
    }

    $component = relationListForm()
        ->call('openRelationPicker', 'city')
        ->set('relationSearch.city', 'As');

    $labels = collect($component->get('relationResults.city'))->pluck('label')->all();

    expect($labels)->toEqualCanonicalizing(['Aswan', 'Asyut']);
});

it('selects a candidate whose name contains an apostrophe', function (): void {
    // 700 seeded cities contain an apostrophe ("N'Goussa", "O'Connor").
    // Interpolating the label into the wire:click expression used to break
    // every one of them, so this asserts the id-only selection path.
    $city = makeListCity("N'Goussa");

    $component = relationListForm()
        ->call('openRelationPicker', 'city')
        ->call('chooseRelation', 'city', $city->id);

    expect($component->get('data.city'))->toBe($city->id)
        ->and($component->get('relationSelected.city.label'))->toBe("N'Goussa");
});

it('refuses an id that was never offered as a candidate', function (): void {
    $offered = makeListCity('Cairo');
    $notOffered = makeListCity('Giza');

    $component = relationListForm()
        ->call('openRelationPicker', 'city')
        ->set('relationSearch.city', 'Cairo')
        ->call('chooseRelation', 'city', $notOffered->id);

    expect($component->get('data.city'))->toBeNull();

    $component->call('chooseRelation', 'city', $offered->id);

    expect($component->get('data.city'))->toBe($offered->id);
});

/**
 * $data is a plain public Livewire property, so the picker's candidate check
 * in chooseRelation() can be skipped entirely by a crafted request. The
 * query() scope therefore has to be enforced by the validation rule too.
 */
it('rejects an existing id that falls outside the field query() scope', function (): void {
    app(FormDefinitionRegistry::class)->register('test.scoped-relation-list', ScopedRelationListFixtureForm::class);

    $outOfScope = City::create([
        'name' => 'Paris',
        'country_id' => $this->country->id,
        'state_id' => 0,
        'country_code' => 'FR',
    ]);
    $inScope = makeListCity('Cairo');

    $component = Livewire::test(Form::class, ['formKey' => 'test.scoped-relation-list'])
        ->set('data.name', 'Acme Corp')
        ->set('data.city', $outOfScope->id)
        ->call('save');

    $component->assertHasErrors('data.city');

    expect(Company::count())->toBe(0);

    $component->set('data.city', $inScope->id)->call('save');

    expect(Company::where('city_id', $inScope->id)->count())->toBe(1);
});

it('rejects a forged id at validation instead of hitting the foreign key', function (): void {
    relationListForm()
        ->set('data.name', 'Acme Corp')
        ->set('data.city', 999999)
        ->call('save');

    expect(Company::count())->toBe(0);
});

/**
 * The inline create button. Its whole risk is that it exposes a second
 * form's create path from a page the actor reached for something else, so
 * every test below is about who may open it and what it may set.
 */
class CreatableRelationListFixtureForm extends RelationListFixtureForm
{
    public function fields(): array
    {
        return [
            TextField::make('name')->label('Name')->required(),
            RelationListField::make('city')
                ->model(City::class)
                ->field('name')
                ->label('City')
                ->createForm('test.city-create'),
        ];
    }
}

/** Same, but the field only ever offers cities of a country that has none. */
class ScopedCreatableRelationListFixtureForm extends CreatableRelationListFixtureForm
{
    public function fields(): array
    {
        return [
            TextField::make('name')->label('Name')->required(),
            RelationListField::make('city')
                ->model(City::class)
                ->field('name')
                ->label('City')
                ->query(fn ($query) => $query->where('country_code', 'XX'))
                ->createForm('test.city-create'),
        ];
    }
}

/** Declares no applicationCode(), so it must never be creatable from elsewhere. */
class UngatedCityCreateFixtureForm extends DynamicForm
{
    public function model(): string
    {
        return City::class;
    }

    public function fields(): array
    {
        return [TextField::make('name')->label('Name')->required()];
    }
}

class GatedCityCreateFixtureForm extends UngatedCityCreateFixtureForm
{
    public function applicationCode(): ?string
    {
        return 'test-city';
    }
}

/** @param  class-string<DynamicForm>  $createForm */
function creatableRelationListForm(string $createForm): Testable
{
    app(FormDefinitionRegistry::class)->register('test.city-create', $createForm);
    app(FormDefinitionRegistry::class)->register('test.creatable', CreatableRelationListFixtureForm::class);

    return Livewire::test(Form::class, ['formKey' => 'test.creatable']);
}

it('offers the create form once its Application is reachable', function (): void {
    Application::factory()->create(['code' => 'test-city', 'is_active' => true, 'permission_name' => null]);

    expect(creatableRelationListForm(GatedCityCreateFixtureForm::class)->instance()->createFormKeyFor('city'))
        ->toBe('test.city-create');
});

it('refuses a create form whose Application is inactive', function (): void {
    Application::factory()->create(['code' => 'test-city', 'is_active' => false, 'permission_name' => null]);

    $component = creatableRelationListForm(GatedCityCreateFixtureForm::class)->call('toggleCreateForm', 'city');

    expect($component->instance()->createFormKeyFor('city'))->toBeNull()
        ->and($component->get('openCreateField'))->toBe('');
});

it('fails closed for a create form that declares no Application at all', function (): void {
    expect(creatableRelationListForm(UngatedCityCreateFixtureForm::class)->instance()->createFormKeyFor('city'))
        ->toBeNull();
});

it('never offers a create button inside an already nested form', function (): void {
    Application::factory()->create(['code' => 'test-city', 'is_active' => true, 'permission_name' => null]);
    app(FormDefinitionRegistry::class)->register('test.city-create', GatedCityCreateFixtureForm::class);
    app(FormDefinitionRegistry::class)->register('test.creatable', CreatableRelationListFixtureForm::class);

    $nested = Livewire::test(Form::class, ['formKey' => 'test.creatable', 'nested' => true]);

    expect($nested->instance()->createFormKeyFor('city'))->toBeNull();
});

it('rejects a nested save whose Application is out of reach', function (): void {
    app(FormDefinitionRegistry::class)->register('test.city-create', UngatedCityCreateFixtureForm::class);

    Livewire::test(Form::class, ['formKey' => 'test.city-create', 'nested' => true])
        ->set('data.name', 'Cairo')
        ->call('save');

    expect(City::where('name', 'Cairo')->count())->toBe(0);
});

it('picks the record created through the nested form', function (): void {
    Application::factory()->create(['code' => 'test-city', 'is_active' => true, 'permission_name' => null]);

    $component = creatableRelationListForm(GatedCityCreateFixtureForm::class)->call('toggleCreateForm', 'city');

    expect($component->get('openCreateField'))->toBe('city');

    $city = makeListCity('Cairo');
    $component->call('selectCreatedRelation', $city->getKey());

    expect($component->get('data.city'))->toBe($city->getKey())
        ->and($component->get('relationSelected.city.label'))->toBe('Cairo')
        ->and($component->get('openCreateField'))->toBe('');
});

it('will not pick a created record the field is not allowed to offer', function (): void {
    Application::factory()->create(['code' => 'test-city', 'is_active' => true, 'permission_name' => null]);
    app(FormDefinitionRegistry::class)->register('test.city-create', GatedCityCreateFixtureForm::class);
    app(FormDefinitionRegistry::class)->register('test.scoped-creatable', ScopedCreatableRelationListFixtureForm::class);

    $component = Livewire::test(Form::class, ['formKey' => 'test.scoped-creatable'])
        ->call('toggleCreateForm', 'city');

    // Created, but outside the field's query() scope.
    $city = makeListCity('Cairo');
    $component->call('selectCreatedRelation', $city->getKey());

    expect($component->get('data.city'))->toBeNull();
});

/**
 * A field pointing at its own definition (an Account's parent is an
 * Account) means the nested form shares the parent's formKey, so its save
 * must NOT look like the parent's own save — that is what the hosting
 * FormModal closes on and the hosting table refreshes on.
 */
class SelfCreatableFixtureForm extends DynamicForm
{
    public function model(): string
    {
        return City::class;
    }

    public function applicationCode(): ?string
    {
        return 'test-city';
    }

    /** Which event a save announces is the whole subject here, not the row. */
    public function create(array $data): Model
    {
        return new City(['name' => $data['name']]);
    }

    public function fields(): array
    {
        return [
            TextField::make('name')->label('Name')->required(),
            RelationListField::make('city')
                ->model(City::class)
                ->field('name')
                ->column('country_id')
                ->label('Nearest City')
                ->createForm('test.self-creatable'),
        ];
    }
}

it('announces a nested save separately from a top-level one', function (): void {
    Application::factory()->create(['code' => 'test-city', 'is_active' => true, 'permission_name' => null]);
    app(FormDefinitionRegistry::class)->register('test.self-creatable', SelfCreatableFixtureForm::class);

    Livewire::test(Form::class, ['formKey' => 'test.self-creatable', 'nested' => true])
        ->set('data.name', 'Cairo')
        ->call('save')
        ->assertDispatched(Form::NESTED_SAVED_EVENT.'test.self-creatable')
        ->assertNotDispatched('dynamic-form-saved.test.self-creatable');

    Livewire::test(Form::class, ['formKey' => 'test.self-creatable'])
        ->set('data.name', 'Giza')
        ->call('save')
        ->assertDispatched('dynamic-form-saved.test.self-creatable')
        ->assertNotDispatched(Form::NESTED_SAVED_EVENT.'test.self-creatable');
});
