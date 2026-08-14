<?php

use Modules\General\Database\Seeders\System\ModulesSeeder;
use Modules\General\Database\Seeders\System\SubModulesSeeder;
use Modules\General\Database\Seeders\World\WorldApplicationsSeeder;
use Modules\General\System\Application;
use Modules\General\System\SubModule;

/**
 * The canonical World applications, keyed by their immutable code.
 *
 * @var array<string, string>
 */
const WORLD_APPLICATION_ROUTES = [
    'gen-wld-ctr' => 'general.world.countries',
    'gen-wld-sta' => 'general.world.states',
    'gen-wld-cty' => 'general.world.cities',
    'gen-wld-tzn' => 'general.world.timezones',
    'gen-wld-cur' => 'general.world.currencies',
    'gen-wld-lng' => 'general.world.languages',
    'gen-wld-com' => 'general.world.companies',
    'gen-wld-per' => 'general.world.people',
];

beforeEach(function (): void {
    (new ModulesSeeder)->run();
    (new SubModulesSeeder)->run();
});

it('seeds all eight world applications under the gen-wld submodule', function (): void {
    (new WorldApplicationsSeeder)->run();

    $worldSubModuleId = SubModule::where('code', 'gen-wld')->value('id');

    expect(Application::count())->toBe(8)
        ->and(Application::where('submodule_id', $worldSubModuleId)->count())->toBe(8);
});

it('seeds the canonical world application codes and route names', function (): void {
    (new WorldApplicationsSeeder)->run();

    $routes = Application::query()->orderBy('sort_order')->pluck('route', 'code')->all();

    expect($routes)->toBe(WORLD_APPLICATION_ROUTES);
});

it('keeps the world application route names free of an index suffix', function (): void {
    (new WorldApplicationsSeeder)->run();

    expect(Application::pluck('route')->all())
        ->each(fn ($route) => $route->not->toContain('.index'));
});

it('creates no duplicates when the seeder runs repeatedly', function (): void {
    (new WorldApplicationsSeeder)->run();
    (new WorldApplicationsSeeder)->run();
    (new WorldApplicationsSeeder)->run();

    expect(Application::count())->toBe(8);

    foreach (array_keys(WORLD_APPLICATION_ROUTES) as $code) {
        expect(Application::where('code', $code)->count())->toBe(1);
    }
});

it('applies the translated attributes and navigation defaults', function (): void {
    (new WorldApplicationsSeeder)->run();

    $countries = Application::where('code', 'gen-wld-ctr')->firstOrFail();

    expect($countries->getTranslation('name', 'ar'))->toBe('الدول')
        ->and($countries->getTranslation('name', 'en'))->toBe('Countries')
        ->and($countries->icon)->toBe('globe-alt')
        ->and($countries->sort_order)->toBe(0)
        ->and($countries->is_active)->toBeTrue()
        ->and($countries->permission_name)->toBeNull()
        ->and($countries->permission_group)->toBeNull();
});

it('fails when the world submodule is missing', function (): void {
    SubModule::where('code', 'gen-wld')->delete();

    (new WorldApplicationsSeeder)->run();
})->throws(RuntimeException::class, 'World submodule with code "gen-wld" was not found.');
