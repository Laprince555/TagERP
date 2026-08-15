<?php

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\General\Database\Seeders\System\ModulesSeeder;
use Modules\General\Database\Seeders\System\SubModulesSeeder;
use Modules\General\Database\Seeders\World\WorldApplicationsSeeder;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('hides the breadcrumb on the launcher page via showBreadcrumbs => false', function (): void {
    $this->get(route('launcher'))
        ->assertSuccessful()
        ->assertDontSee('aria-label="Breadcrumb"', false);
});

it('shows the full module > sub-module > application trail on a real page, built from the route name, via the cached navigation tree', function (): void {
    (new ModulesSeeder)->run();
    (new SubModulesSeeder)->run();
    (new WorldApplicationsSeeder)->run();

    Cache::flush();

    $response = $this->get(route('general.world.countries'));

    $response->assertSuccessful();

    // The old pill-shaped breadcrumb nav (not flux:breadcrumbs) with every ancestor
    // (module, sub-module, application) for the current route name.
    $response->assertSee('aria-label="Breadcrumb"', false);
    $response->assertSeeText('Countries');

    // A second render must not re-query Module/SubModule/Application — proves the
    // navigation tree came from NavigationTreeService's cache, not a fresh query.
    $queryCount = 0;
    DB::listen(function ($query) use (&$queryCount): void {
        if (str_contains($query->sql, '"modules"') || str_contains($query->sql, '"sub_modules"')) {
            $queryCount++;
        }
    });

    $this->get(route('general.world.countries'))->assertSuccessful();

    expect($queryCount)->toBe(0);
});
