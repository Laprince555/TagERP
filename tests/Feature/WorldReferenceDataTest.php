<?php

use App\Models\User;
use Livewire\Livewire;
use Modules\General\Database\Seeders\System\ModulesSeeder;
use Modules\General\Database\Seeders\System\SubModulesSeeder;
use Modules\General\Database\Seeders\World\WorldApplicationsSeeder;
use Modules\General\Livewire\World\Countries\CitiesTable;
use Modules\General\Livewire\World\Currencies\CurrenciesTable;
use Modules\General\Livewire\World\States\StatesTable;
use Modules\General\Livewire\World\Timezones\TimezonesTable;
use Modules\General\System\Application;
use Nnjeim\World\Models\City;
use Nnjeim\World\Models\Country;
use Nnjeim\World\Models\Currency;
use Nnjeim\World\Models\State;
use Nnjeim\World\Models\Timezone;

/**
 * Smoke coverage for the States/Cities/Currencies/Timezones vertical
 * slices, mirroring Countries: each index route renders its Dynamic Table,
 * each Country-relation column renders as a Record Reference tag, each show
 * route renders and can be disabled per Application, exactly like
 * tests/Feature/CountriesIndexTest.php and
 * tests/Feature/RecordReference/CountryShowRouteTest.php.
 */
beforeEach(function (): void {
    (new ModulesSeeder)->run();
    (new SubModulesSeeder)->run();
    (new WorldApplicationsSeeder)->run();

    $this->actingAs(User::factory()->create());
});

function makeReferenceCountry(): Country
{
    return Country::create([
        'name' => 'Egypt',
        'iso2' => 'EG',
        'iso3' => 'EGY',
        'phone_code' => '20',
        'region' => 'Africa',
        'subregion' => 'Northern Africa',
        'status' => 1,
    ]);
}

it('renders the states index page and the country tag for each row', function (): void {
    $country = makeReferenceCountry();
    State::create(['name' => 'Cairo Governorate', 'country_id' => $country->id, 'country_code' => 'EG']);

    $this->get(route('general.world.states'))
        ->assertSuccessful()
        ->assertSee('States')
        ->assertSee('gen-wld-sta')
        ->assertSeeLivewire(StatesTable::class);

    Livewire::test(StatesTable::class)
        ->assertSee('Cairo Governorate')
        ->assertSeeHtml(route('general.world.countries.show', ['recordId' => $country->id]));
});

it('renders the states show route and denies access when the application is inactive', function (): void {
    $country = makeReferenceCountry();
    $state = State::create(['name' => 'Cairo Governorate', 'country_id' => $country->id, 'country_code' => 'EG']);

    $this->get(route('general.world.states.show', ['recordId' => $state->id]))
        ->assertOk()
        ->assertSee('Cairo Governorate');

    // A mass-update query bypasses Eloquent events entirely, so it would
    // never reach NavigationObserver and the cached Application would stay
    // stale — fetch the instance first, same as CountryShowRouteTest.
    Application::where('code', 'gen-wld-sta')->first()->update(['is_active' => false]);

    $this->get(route('general.world.states.show', ['recordId' => $state->id]))
        ->assertNotFound();
});

it('renders the cities index page and the country/state tags for each row', function (): void {
    $country = makeReferenceCountry();
    $state = State::create(['name' => 'Cairo Governorate', 'country_id' => $country->id, 'country_code' => 'EG']);
    City::create(['name' => 'Cairo', 'country_id' => $country->id, 'state_id' => $state->id, 'country_code' => 'EG']);

    $this->get(route('general.world.cities'))
        ->assertSuccessful()
        ->assertSee('Cities')
        ->assertSee('gen-wld-cty')
        ->assertSeeLivewire(CitiesTable::class);

    Livewire::test(CitiesTable::class)
        ->assertSee('Cairo')
        ->assertSeeHtml(route('general.world.countries.show', ['recordId' => $country->id]))
        ->assertSeeHtml(route('general.world.states.show', ['recordId' => $state->id]));
});

it('renders the cities show route and denies access when the application is inactive', function (): void {
    $country = makeReferenceCountry();
    $city = City::create(['name' => 'Cairo', 'country_id' => $country->id, 'state_id' => 0, 'country_code' => 'EG']);

    $this->get(route('general.world.cities.show', ['recordId' => $city->id]))
        ->assertOk()
        ->assertSee('Cairo');

    Application::where('code', 'gen-wld-cty')->first()->update(['is_active' => false]);

    $this->get(route('general.world.cities.show', ['recordId' => $city->id]))
        ->assertNotFound();
});

it('renders the currencies index page and the country tag for each row', function (): void {
    $country = makeReferenceCountry();
    Currency::create(['name' => 'Egyptian Pound', 'code' => 'EGP', 'country_id' => $country->id, 'precision' => 2, 'symbol' => 'E£', 'symbol_native' => 'ج.م.', 'symbol_first' => true, 'decimal_mark' => '.', 'thousands_separator' => ',']);

    $this->get(route('general.world.currencies'))
        ->assertSuccessful()
        ->assertSee('Currencies')
        ->assertSee('gen-wld-cur')
        ->assertSeeLivewire(CurrenciesTable::class);

    Livewire::test(CurrenciesTable::class)
        ->assertSee('Egyptian Pound')
        ->assertSeeHtml(route('general.world.countries.show', ['recordId' => $country->id]));
});

it('renders the currencies show route and denies access when the application is inactive', function (): void {
    $country = makeReferenceCountry();
    $currency = Currency::create(['name' => 'Egyptian Pound', 'code' => 'EGP', 'country_id' => $country->id, 'precision' => 2, 'symbol' => 'E£', 'symbol_native' => 'ج.م.', 'symbol_first' => true, 'decimal_mark' => '.', 'thousands_separator' => ',']);

    $this->get(route('general.world.currencies.show', ['recordId' => $currency->id]))
        ->assertOk()
        ->assertSee('Egyptian Pound');

    Application::where('code', 'gen-wld-cur')->first()->update(['is_active' => false]);

    $this->get(route('general.world.currencies.show', ['recordId' => $currency->id]))
        ->assertNotFound();
});

it('renders the timezones index page and the country tag for each row', function (): void {
    $country = makeReferenceCountry();
    Timezone::create(['name' => 'Africa/Cairo', 'country_id' => $country->id]);

    $this->get(route('general.world.timezones'))
        ->assertSuccessful()
        ->assertSee('Time Zones')
        ->assertSee('gen-wld-tzn')
        ->assertSeeLivewire(TimezonesTable::class);

    Livewire::test(TimezonesTable::class)
        ->assertSee('Africa/Cairo')
        ->assertSeeHtml(route('general.world.countries.show', ['recordId' => $country->id]));
});

it('renders the timezones show route and denies access when the application is inactive', function (): void {
    $country = makeReferenceCountry();
    $timezone = Timezone::create(['name' => 'Africa/Cairo', 'country_id' => $country->id]);

    $this->get(route('general.world.timezones.show', ['recordId' => $timezone->id]))
        ->assertOk()
        ->assertSee('Africa/Cairo');

    Application::where('code', 'gen-wld-tzn')->first()->update(['is_active' => false]);

    $this->get(route('general.world.timezones.show', ['recordId' => $timezone->id]))
        ->assertNotFound();
});
