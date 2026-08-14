<?php

use App\Models\User;
use App\Support\DynamicRecordView\Resolution\RelationshipMutator;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\General\Database\Seeders\System\ModulesSeeder;
use Modules\General\Database\Seeders\System\SubModulesSeeder;
use Modules\General\Database\Seeders\World\WorldApplicationsSeeder;
use Modules\General\Livewire\World\Countries\CitiesTable;
use Nnjeim\World\Models\City;
use Nnjeim\World\Models\Country;

/**
 * Phase 7: the canonical Country -> Cities embedded-table example.
 * City.country_id is NOT NULL (see vendor/nnjeim/world .../create_cities_table.php),
 * so this relation is wired Link-only with allowReassignment(), same as
 * SubModuleRecordView's Applications relation.
 */
beforeEach(function (): void {
    (new ModulesSeeder)->run();
    (new SubModulesSeeder)->run();
    (new WorldApplicationsSeeder)->run();
    $this->actingAs(User::factory()->create());
});

function makeCityCountry(array $overrides = []): Country
{
    return Country::create(array_merge([
        'name' => 'Egypt',
        'iso2' => 'EG',
        'iso3' => 'EGY',
        'phone_code' => '20',
        'region' => 'Africa',
        'subregion' => 'Northern Africa',
        'status' => 1,
    ], $overrides));
}

function mountEmbeddedCitiesTable(int|string $countryId)
{
    return Livewire::test(CitiesTable::class, [
        'embedRecordViewKey' => 'general.world.country',
        'embedRecordId' => $countryId,
        'embedSection' => 'other-data',
        'embedTab' => 'cities',
        'embedContent' => 'cities-table',
    ]);
}

it('renders the Country record route for a real country', function (): void {
    $country = makeCityCountry();

    $response = $this->get(route('general.world.countries.show', ['recordId' => $country->id]));

    $response->assertOk()->assertSee('Egypt');
});

it('shows only the opened country cities and not another country cities, including under a crafted search', function (): void {
    $countryA = makeCityCountry(['name' => 'Egypt', 'iso2' => 'EG']);
    $countryB = makeCityCountry(['name' => 'Saudi Arabia', 'iso2' => 'SA', 'iso3' => 'SAU', 'phone_code' => '966']);

    City::create(['country_id' => $countryA->id, 'state_id' => 0, 'name' => 'Cairo', 'country_code' => 'EG']);
    City::create(['country_id' => $countryA->id, 'state_id' => 0, 'name' => 'Giza', 'country_code' => 'EG']);
    City::create(['country_id' => $countryB->id, 'state_id' => 0, 'name' => 'Riyadh-only-city', 'country_code' => 'SA']);

    $component = mountEmbeddedCitiesTable($countryA->id);

    $component->assertViewHas('rows', function ($rows) {
        $names = collect($rows->items())->pluck('name');

        return $rows->total() === 2
            && $names->sort()->values()->all() === ['Cairo', 'Giza'];
    });

    // A crafted search that only matches Country B's data must never leak in.
    $component->set('search', 'Riyadh-only-city')->call('submitSearch');
    $component->assertViewHas('rows', fn ($rows) => $rows->isEmpty());
});

it('works standalone unconstrained with no embedding-specific requirement', function (): void {
    $country = makeCityCountry();
    City::create(['country_id' => $country->id, 'state_id' => 0, 'name' => 'Alexandria', 'country_code' => 'EG']);

    Livewire::test(CitiesTable::class)
        ->assertViewHas('rows', fn ($rows) => $rows->total() >= 1);
});

it('links a city from another country into the opened country, reassigning its country_id', function (): void {
    $origin = makeCityCountry(['name' => 'Egypt', 'iso2' => 'EG']);
    $target = makeCityCountry(['name' => 'Saudi Arabia', 'iso2' => 'SA', 'iso3' => 'SAU', 'phone_code' => '966']);
    $city = City::create(['country_id' => $origin->id, 'state_id' => 0, 'name' => 'Cairo', 'country_code' => 'EG']);

    app(RelationshipMutator::class)->link(
        CitiesTable::class,
        'general.world.country',
        $target->id,
        'other-data',
        'cities',
        'cities-table',
        $city->id,
    );

    expect($city->refresh()->country_id)->toBe($target->id);
});

it('keeps a constant query count regardless of related-city count', function (): void {
    $small = makeCityCountry(['name' => 'Egypt', 'iso2' => 'EG']);
    for ($i = 0; $i < 5; $i++) {
        City::create(['country_id' => $small->id, 'state_id' => 0, 'name' => "City-{$i}", 'country_code' => 'EG']);
    }

    $large = makeCityCountry(['name' => 'Saudi Arabia', 'iso2' => 'SA', 'iso3' => 'SAU', 'phone_code' => '966']);
    for ($i = 0; $i < 100; $i++) {
        City::create(['country_id' => $large->id, 'state_id' => 0, 'name' => "City-{$i}", 'country_code' => 'SA']);
    }

    DB::enableQueryLog();
    mountEmbeddedCitiesTable($small->id);
    $smallCount = count(DB::getQueryLog());

    DB::flushQueryLog();
    mountEmbeddedCitiesTable($large->id);
    $largeCount = count(DB::getQueryLog());

    expect($largeCount)->toBe($smallCount);
});
