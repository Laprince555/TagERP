<?php

use App\Livewire\DynamicForm\Form;
use App\Models\User;
use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\RelationListField;
use App\Support\DynamicForm\Core\Fields\TextField;
use App\Support\DynamicForm\Core\FormDefinitionRegistry;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Modules\General\Models\World\Companies\Company;
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
