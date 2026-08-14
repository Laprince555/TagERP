<?php

use App\Models\User;
use App\Services\NavigationTreeService;
use App\Support\ModuleRoute;
use Illuminate\Support\Facades\Route;
use Modules\General\Livewire\SubModuleWorkspace;
use Modules\General\System\Application;
use Modules\General\System\Module;
use Modules\General\System\SubModule;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

/**
 * Sub module routes are derived from `sub_modules.route` while the route files load,
 * which in a real request happens after the rows already exist. The test application
 * boots before any row is seeded, so the registration is replayed here instead of
 * rebooting the app, which would drop the in-memory database with it.
 */
function publishSubModuleRoutes(string $moduleRouteName = 'general', string $path = '/general'): void
{
    app()->forgetInstance(NavigationTreeService::class);
    cache()->clear();

    ModuleRoute::registerSubModules($moduleRouteName, $path, SubModuleWorkspace::class);

    Route::getRoutes()->refreshNameLookups();
    Route::getRoutes()->refreshActionLookups();
}

/**
 * The layout embeds the whole navigation tree for the command palette, so every sub
 * module name appears somewhere in the document. Assertions about which sub module the
 * page actually resolved therefore read the page heading rather than the raw body.
 */
function renderedHeading(string $html): string
{
    preg_match('/<h1[^>]*>(.*?)<\/h1>/s', $html, $matches);

    return trim(html_entity_decode($matches[1] ?? '', ENT_QUOTES));
}

/**
 * @param  array<string, mixed>  $attributes
 * @param  array<string, mixed>  $moduleAttributes
 */
function seedSubModule(array $attributes = [], array $moduleAttributes = []): SubModule
{
    $module = Module::factory()->create([
        'code' => 'gen',
        'route' => 'general',
        'name' => ['en' => 'General Module'],
        ...$moduleAttributes,
    ]);

    return SubModule::factory()->for($module)->create([
        'code' => 'gen-wld',
        'route' => 'general.world',
        'name' => ['en' => 'World & Geography'],
        'description' => ['en' => 'Countries, regions and cities'],
        ...$attributes,
    ]);
}

it('renders the sub module whose database route matches the current route name', function (): void {
    seedSubModule();
    publishSubModuleRoutes();

    $response = $this->get('/en/general/world')
        ->assertSuccessful()
        ->assertSee('Countries, regions and cities')
        ->assertSee('gen-wld');

    expect(renderedHeading($response->getContent()))->toBe('World & Geography');
});

it('serves different sub modules from the same component through the route name alone', function (): void {
    $module = Module::factory()->create(['code' => 'gen', 'route' => 'general']);

    SubModule::factory()->for($module)->create([
        'code' => 'gen-wld',
        'route' => 'general.world',
        'name' => ['en' => 'World & Geography'],
    ]);

    SubModule::factory()->for($module)->create([
        'code' => 'gen-sys',
        'route' => 'general.system',
        'name' => ['en' => 'System Settings'],
    ]);

    publishSubModuleRoutes();

    $world = $this->get('/en/general/world')->assertSuccessful();
    $system = $this->get('/en/general/system')->assertSuccessful();

    expect(renderedHeading($world->getContent()))->toBe('World & Geography');
    expect(renderedHeading($system->getContent()))->toBe('System Settings');
});

it('breadcrumbs back to the owning module', function (): void {
    seedSubModule();
    publishSubModuleRoutes();

    $this->get('/en/general/world')
        ->assertSuccessful()
        ->assertSee('General Module')
        ->assertSee('href="'.route('general', ['locale' => 'en']).'"', escape: false);
});

it('lists the applications of the sub module ordered by sort order', function (): void {
    $subModule = seedSubModule();

    Application::factory()->for($subModule, 'subModule')->create([
        'name' => ['en' => 'Cities Registry'],
        'sort_order' => 2,
    ]);

    Application::factory()->for($subModule, 'subModule')->create([
        'name' => ['en' => 'Countries Registry'],
        'sort_order' => 0,
    ]);

    Application::factory()->for($subModule, 'subModule')->create([
        'name' => ['en' => 'States Registry'],
        'sort_order' => 1,
    ]);

    publishSubModuleRoutes();

    $this->get('/en/general/world')
        ->assertSuccessful()
        ->assertSeeInOrder(['Countries Registry', 'States Registry', 'Cities Registry'])
        ->assertSee('3 applications');
});

it('hides inactive applications and the ones the user has no permission for', function (): void {
    $subModule = seedSubModule();

    Application::factory()->for($subModule, 'subModule')->create(['name' => ['en' => 'Visible App']]);
    Application::factory()->for($subModule, 'subModule')->inactive()->create(['name' => ['en' => 'Inactive App']]);
    Application::factory()
        ->for($subModule, 'subModule')
        ->requiringPermission('manage_restricted_app')
        ->create(['name' => ['en' => 'Restricted App']]);

    publishSubModuleRoutes();

    $this->get('/en/general/world')
        ->assertSuccessful()
        ->assertSee('Visible App')
        ->assertDontSee('Inactive App')
        ->assertDontSee('Restricted App')
        ->assertSee('1 application');
});

it('links an application whose route is registered and disables one that is not', function (): void {
    $subModule = seedSubModule();

    Application::factory()->for($subModule, 'subModule')->create([
        'name' => ['en' => 'Linked App'],
        'route' => 'launcher',
        'sort_order' => 0,
    ]);

    Application::factory()->for($subModule, 'subModule')->create([
        'name' => ['en' => 'Unwired App'],
        'route' => 'general.world.nowhere',
        'sort_order' => 1,
    ]);

    publishSubModuleRoutes();

    $this->get('/en/general/world')
        ->assertSuccessful()
        ->assertSee('href="'.route('launcher').'"', escape: false)
        ->assertSee(__('messages.workspace.unavailable'))
        ->assertDontSee('href="#"', escape: false);
});

it('renders the empty state for a sub module without applications', function (): void {
    seedSubModule();
    publishSubModuleRoutes();

    $this->get('/en/general/world')
        ->assertSuccessful()
        ->assertSee(__('messages.workspace.applications_empty_title'));
});

it('shows the pending tasks placeholder without implying any task data', function (): void {
    seedSubModule();
    publishSubModuleRoutes();

    $this->get('/en/general/world')
        ->assertSuccessful()
        ->assertSee(__('messages.workspace.pending_tasks'))
        ->assertSee(__('messages.workspace.view_all_tasks'))
        ->assertSee(__('messages.workspace.completion_rate'))
        ->assertSee(__('messages.workspace.coming_soon'));
});

it('does not register a route for a sub module that has no database row', function (): void {
    seedSubModule();
    publishSubModuleRoutes();

    $this->get('/en/general/does-not-exist')->assertNotFound();
});

it('returns 404 when the sub module behind a registered route is inactive', function (): void {
    seedSubModule(['is_active' => false]);
    publishSubModuleRoutes();

    $this->get('/en/general/world')->assertNotFound();
});

it('returns 404 when the owning module is inactive', function (): void {
    seedSubModule(moduleAttributes: ['is_active' => false]);
    publishSubModuleRoutes();

    $this->get('/en/general/world')->assertNotFound();
});

it('requires authentication', function (): void {
    seedSubModule();
    publishSubModuleRoutes();

    auth()->logout();

    $this->get('/en/general/world')->assertRedirect(route('login'));
});
