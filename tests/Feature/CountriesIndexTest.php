<?php

use App\Models\User;
use Livewire\Livewire;
use Modules\General\Database\Seeders\System\ModulesSeeder;
use Modules\General\Database\Seeders\System\SubModulesSeeder;
use Modules\General\Database\Seeders\World\WorldApplicationsSeeder;
use Modules\General\Livewire\World\Countries\CountriesTable;
use Modules\General\System\Application;
use Nnjeim\World\Models\Country;

beforeEach(function (): void {
    (new ModulesSeeder)->run();
    (new SubModulesSeeder)->run();
    (new WorldApplicationsSeeder)->run();

    $this->actingAs(User::factory()->create());
});

it('renders the countries index page with application header and dynamic table', function (): void {
    Country::create([
        'name' => 'Egypt',
        'iso2' => 'EG',
        'iso3' => 'EGY',
        'phone_code' => '20',
        'region' => 'Africa',
        'subregion' => 'Northern Africa',
        'status' => 1,
    ]);

    $this->get(route('general.world.countries'))
        ->assertSuccessful()
        ->assertSee('Countries')
        ->assertSee('gen-wld-ctr')
        ->assertSeeLivewire(CountriesTable::class);
});

it('denies access to inactive application', function (): void {
    Application::where('code', 'gen-wld-ctr')->update(['is_active' => false]);

    $this->get(route('general.world.countries'))
        ->assertNotFound();
});

it('dynamic table displays country columns and allows search and filtering', function (): void {
    Country::create([
        'name' => 'Egypt',
        'iso2' => 'EG',
        'iso3' => 'EGY',
        'phone_code' => '20',
        'region' => 'Africa',
        'subregion' => 'Northern Africa',
        'status' => 1,
    ]);

    Country::create([
        'name' => 'France',
        'iso2' => 'FR',
        'iso3' => 'FRA',
        'phone_code' => '33',
        'region' => 'Europe',
        'subregion' => 'Western Europe',
        'status' => 1,
    ]);

    Livewire::test(CountriesTable::class)
        ->assertSee('Egypt')
        ->assertSee('France')
        ->assertSee('EG')
        ->assertSee('FR')
        ->set('search', 'Egypt')
        ->call('submitSearch')
        ->assertSee('Egypt')
        ->assertDontSee('France');
});

it('applies a text filter even when only its value was ever touched', function (): void {
    // Regression: the operator <select> defaults to "Contains" in the DOM but
    // Livewire never syncs an untouched select's value unless the component
    // seeds it server-side — leaving filters.<key>.operator missing, which
    // TableState::normalizeFilterEntry() then silently drops, so the filter
    // had zero effect on the query despite the UI showing an "applied" chip.
    Country::create([
        'name' => 'Egypt',
        'iso2' => 'EG',
        'iso3' => 'EGY',
        'phone_code' => '20',
        'region' => 'Africa',
        'subregion' => 'Northern Africa',
        'status' => 1,
    ]);

    Country::create([
        'name' => 'France',
        'iso2' => 'FR',
        'iso3' => 'FRA',
        'phone_code' => '33',
        'region' => 'Europe',
        'subregion' => 'Western Europe',
        'status' => 1,
    ]);

    Livewire::test(CountriesTable::class)
        ->set('filters.region.value', 'Africa')
        ->call('applyFilters')
        ->assertSee('Egypt')
        ->assertDontSee('France');
});

it('renders the name column as a linked record reference', function (): void {
    // Regression: the Livewire wrapper view built $referenceCells in render()
    // but never passed it to <x-dynamic-table.table> — the component's
    // :reference-cells prop silently defaulted to [], so every
    // RecordReferenceColumn cell fell through to plain unlinked text.
    Country::create([
        'name' => 'Egypt',
        'iso2' => 'EG',
        'iso3' => 'EGY',
        'phone_code' => '20',
        'region' => 'Africa',
        'subregion' => 'Northern Africa',
        'status' => 1,
    ]);

    Livewire::test(CountriesTable::class)
        ->assertSeeHtml(route('general.world.countries.show', ['recordId' => Country::where('name', 'Egypt')->first()->id]));
});
