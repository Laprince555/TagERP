<?php

use App\Models\User;
use Modules\General\Database\Seeders\System\ModulesSeeder;
use Modules\General\Database\Seeders\System\SubModulesSeeder;
use Modules\General\Database\Seeders\World\WorldApplicationsSeeder;
use Modules\General\Livewire\World\Countries\CountriesIndex;
use Modules\General\Livewire\World\Countries\CountriesTable;
use Modules\General\Livewire\World\Countries\CountryRecordView;
use Modules\General\System\Application;
use Nnjeim\World\Models\Country;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

beforeEach(function (): void {
    (new ModulesSeeder)->run();
    (new SubModulesSeeder)->run();
    (new WorldApplicationsSeeder)->run();
});

it('generates a real, named, route-model-bound url for the record reference', function (): void {
    $country = Country::create(['name' => 'Egypt', 'iso2' => 'EG', 'iso3' => 'EGY', 'phone_code' => '20', 'region' => 'Africa', 'subregion' => 'Northern Africa', 'status' => 1]);

    expect(route('general.world.countries.show', ['recordId' => $country->id]))
        ->toBe(url("/general/world/countries/{$country->id}/view"));
});

it('renders the Country show page for an authenticated user', function (): void {
    $country = Country::create(['name' => 'Egypt', 'iso2' => 'EG', 'iso3' => 'EGY', 'phone_code' => '20', 'region' => 'Africa', 'subregion' => 'Northern Africa', 'status' => 1]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('general.world.countries.show', ['recordId' => $country->id]))
        ->assertOk()
        ->assertSee('Egypt');
});

it('redirects a guest away from the record show route', function (): void {
    $country = Country::create(['name' => 'Egypt', 'iso2' => 'EG', 'iso3' => 'EGY', 'phone_code' => '20', 'region' => 'Africa', 'subregion' => 'Northern Africa', 'status' => 1]);

    $this->get(route('general.world.countries.show', ['recordId' => $country->id]))
        ->assertRedirect();
});

it('404s for a record id that does not exist, never leaking existence via a different status', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('general.world.countries.show', ['recordId' => 999999]))
        ->assertNotFound();
});

it('returns 404 for show route when Application is inactive', function (): void {
    $country = Country::create(['name' => 'Egypt', 'iso2' => 'EG', 'iso3' => 'EGY', 'phone_code' => '20', 'region' => 'Africa', 'subregion' => 'Northern Africa', 'status' => 1]);
    $user = User::factory()->create();

    $application = Application::where('code', 'gen-wld-ctr')->first();
    $application->update(['is_active' => false]);

    $this->actingAs($user)
        ->get(route('general.world.countries.show', ['recordId' => $country->id]))
        ->assertNotFound();
});

it('enforces revoked access on subsequent Livewire table request', function (): void {
    $this->withoutExceptionHandling();
    $user = User::factory()->create();
    $this->actingAs($user);

    $component = Livewire\Livewire::test(CountriesTable::class);
    $component->assertOk();

    // Now deactivate application
    $application = Application::where('code', 'gen-wld-ctr')->first();
    $application->update(['is_active' => false]);

    // Subsequent request must fail closed with 404
    expect(fn () => $component->call('$refresh'))->toThrow(NotFoundHttpException::class);
});

it('enforces revoked access on subsequent Livewire index request', function (): void {
    $this->withoutExceptionHandling();
    $user = User::factory()->create();
    $this->actingAs($user);

    $component = Livewire\Livewire::test(CountriesIndex::class);
    $component->assertOk();

    // Now deactivate application
    $application = Application::where('code', 'gen-wld-ctr')->first();
    $application->update(['is_active' => false]);

    // Subsequent request must fail closed with 404
    expect(fn () => $component->call('$refresh'))->toThrow(NotFoundHttpException::class);
});

it('enforces revoked access on subsequent Livewire show/view request', function (): void {
    $this->withoutExceptionHandling();
    $country = Country::create(['name' => 'Egypt', 'iso2' => 'EG', 'iso3' => 'EGY', 'phone_code' => '20', 'region' => 'Africa', 'subregion' => 'Northern Africa', 'status' => 1]);
    $user = User::factory()->create();
    $this->actingAs($user);

    $component = Livewire\Livewire::test(CountryRecordView::class, ['recordId' => $country->id]);
    $component->assertOk();

    // Now deactivate application
    $application = Application::where('code', 'gen-wld-ctr')->first();
    $application->update(['is_active' => false]);

    // Subsequent request must fail closed with 404
    expect(fn () => $component->call('$refresh'))->toThrow(NotFoundHttpException::class);
});
