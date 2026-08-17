<?php

use App\Livewire\DynamicForm\Form;
use App\Livewire\DynamicForm\FormModal;
use Livewire\Livewire;
use Modules\General\Database\Seeders\System\ModulesSeeder;
use Modules\General\Database\Seeders\System\SubModulesSeeder;
use Modules\General\Database\Seeders\World\WorldApplicationsSeeder;
use Modules\General\Livewire\World\People\PeopleTable;
use Modules\General\Models\World\People\Gender;
use Modules\General\Models\World\People\Person;
use Nnjeim\World\Models\City;
use Nnjeim\World\Models\Country;
use Nnjeim\World\Models\State;

beforeEach(function (): void {
    (new ModulesSeeder)->run();
    (new SubModulesSeeder)->run();
    (new WorldApplicationsSeeder)->run();

    $this->actingAs(superAdmin());
});

/** Builds a City reachable through the full Country -> State -> City cascade. */
function makeFormCity(string $name): City
{
    $country = Country::firstOrCreate(
        ['iso2' => 'EG'],
        ['name' => 'Egypt', 'iso3' => 'EGY', 'phone_code' => '20', 'region' => 'Africa', 'subregion' => 'Northern Africa', 'status' => 1],
    );

    $state = State::firstOrCreate(
        ['name' => 'Alexandria', 'country_id' => $country->id],
        ['country_code' => 'EG'],
    );

    return City::create([
        'name' => $name,
        'country_id' => $country->id,
        'state_id' => $state->id,
        'country_code' => 'EG',
    ]);
}

it('shows an Add Person button on the people table wired to its form key', function (): void {
    Livewire::test(PeopleTable::class)
        ->assertSee('Add Person')
        ->assertSeeHtml('open-form-modal.general.world.person.create');
});

/**
 * The button rendering is not enough: $wire.on() only fires for events this
 * component itself dispatches, so the modal stays shut unless FormModal
 * declares a server-side listener that relays the toolbar's event back out.
 * This asserts that relay, which is what actually makes the button work.
 */
it('relays the toolbar open event into the event the modal listens for', function (): void {
    Livewire::test(FormModal::class, ['formKey' => 'general.world.person.create'])
        ->dispatch('open-form-modal.general.world.person.create')
        ->assertDispatched('form-modal-opened.general.world.person.create');
});

it('closes itself after a successful save', function (): void {
    Livewire::test(FormModal::class, ['formKey' => 'general.world.person.create'])
        ->dispatch('dynamic-form-saved.general.world.person.create')
        ->assertDispatched('close-form-modal.general.world.person.create');
});

it('renders every declared field for the person form', function (): void {
    Livewire::test(Form::class, ['formKey' => 'general.world.person.create'])
        ->assertSee('Full Name')
        ->assertSee('Nickname')
        ->assertSee('National ID')
        ->assertSee('Passport Number')
        ->assertSee('Gender')
        ->assertSee('Date of Birth')
        ->assertSee('City')
        ->assertSee('Address')
        ->assertSee('Phone')
        ->assertSee('Email');
});

it('rejects a person with no full name', function (): void {
    Livewire::test(Form::class, ['formKey' => 'general.world.person.create'])
        ->set('data.full_name', '')
        ->call('save');

    expect(Person::count())->toBe(0);
});

it('creates a Person with a generated hierarchical code', function (): void {
    Livewire::test(Form::class, ['formKey' => 'general.world.person.create'])
        ->set('data.full_name', 'Ahmed Ali')
        ->set('data.national_id', '12345')
        ->set('data.gender', Gender::Male->value)
        ->call('save')
        ->assertDispatched('dynamic-form-saved.general.world.person.create');

    $person = Person::where('full_name', 'Ahmed Ali')->firstOrFail();

    expect($person->code)->toBe('gen-wld-per-ahmed-ali-12345')
        ->and($person->gender)->toBe(Gender::Male);
});

it('picks a city whose name contains an apostrophe', function (): void {
    // 700 seeded cities contain an apostrophe ("N'Goussa", "O'Connor").
    // Interpolating the label into the wire:click expression used to break
    // every one of them, so this asserts the id-only selection path.
    $city = makeFormCity("N'Goussa");

    Livewire::test(Form::class, ['formKey' => 'general.world.person.create'])
        ->call('openCascadePicker', 'city')
        ->call('chooseCascade', 'city', 'country', $city->country_id)
        ->call('chooseCascade', 'city', 'state', $city->state_id)
        ->call('chooseCascade', 'city', 'city', $city->id)
        ->set('data.full_name', 'Ahmed Ali')
        ->call('save');

    expect(Person::where('full_name', 'Ahmed Ali')->firstOrFail()->city_id)->toBe($city->id);
});

it('rejects a forged city id at validation instead of hitting the foreign key', function (): void {
    Livewire::test(Form::class, ['formKey' => 'general.world.person.create'])
        ->set('data.full_name', 'Ahmed Ali')
        ->set('data.city', 999999)
        ->call('save');

    expect(Person::count())->toBe(0);
});
