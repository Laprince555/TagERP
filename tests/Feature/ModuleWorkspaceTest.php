<?php

use App\Models\User;
use App\Services\NavigationTreeService;
use Modules\General\System\Application;
use Modules\General\System\Module;
use Modules\General\System\SubModule;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

/**
 * The navigation tree is memoized on a singleton and cached per user, so it has to be
 * released whenever a test seeds navigation records after the service was resolved.
 */
function forgetNavigationTree(): void
{
    app()->forgetInstance(NavigationTreeService::class);
    cache()->clear();
}

it('renders a registered module route whose database module matches the route name', function (): void {
    Module::factory()->create([
        'code' => 'gen',
        'route' => 'general',
        'name' => ['en' => 'General Module', 'ar' => 'الوحدة العامة'],
        'description' => ['en' => 'System wide configuration', 'ar' => 'إعدادات النظام'],
    ]);

    forgetNavigationTree();

    $this->get('/en/general')
        ->assertSuccessful()
        ->assertSee('General Module')
        ->assertSee('System wide configuration');
});

it('resolves the module from the current named route', function (): void {
    Module::factory()->create([
        'code' => 'gen',
        'route' => 'general',
        'name' => ['en' => 'General Module'],
    ]);

    Module::factory()->create([
        'code' => 'fin',
        'route' => 'finance',
        'name' => ['en' => 'Finance Module'],
    ]);

    forgetNavigationTree();

    $this->get('/en/finance')
        ->assertSuccessful()
        ->assertSee('Finance Module')
        ->assertSee('data-module-code="fin"', escape: false)
        ->assertDontSee('data-module-code="gen"', escape: false);

    $this->get('/en/general')
        ->assertSuccessful()
        ->assertSee('data-module-code="gen"', escape: false)
        ->assertDontSee('data-module-code="fin"', escape: false);
});

it('displays active sub modules ordered by sort order', function (): void {
    $module = Module::factory()->create(['code' => 'gen', 'route' => 'general']);

    SubModule::factory()->for($module)->create([
        'code' => 'gen-sec',
        'name' => ['en' => 'Security Roles'],
        'sort_order' => 2,
    ]);

    SubModule::factory()->for($module)->create([
        'code' => 'gen-sys',
        'name' => ['en' => 'System Settings'],
        'sort_order' => 0,
    ]);

    SubModule::factory()->for($module)->create([
        'code' => 'gen-wld',
        'name' => ['en' => 'World Geography'],
        'sort_order' => 1,
    ]);

    forgetNavigationTree();

    $this->get('/en/general')
        ->assertSuccessful()
        ->assertSeeInOrder(['System Settings', 'World Geography', 'Security Roles']);
});

it('hides inactive sub modules', function (): void {
    $module = Module::factory()->create(['code' => 'gen', 'route' => 'general']);

    SubModule::factory()->for($module)->create(['name' => ['en' => 'Visible SubModule']]);
    SubModule::factory()->for($module)->inactive()->create(['name' => ['en' => 'Hidden SubModule']]);

    forgetNavigationTree();

    $this->get('/en/general')
        ->assertSuccessful()
        ->assertSee('Visible SubModule')
        ->assertDontSee('Hidden SubModule');
});

it('counts only the applications the user is allowed to see', function (): void {
    $module = Module::factory()->create(['code' => 'gen', 'route' => 'general']);
    $subModule = SubModule::factory()->for($module)->create(['name' => ['en' => 'System Settings']]);

    Application::factory()->for($subModule, 'subModule')->create(['name' => ['en' => 'Visible App']]);
    Application::factory()->for($subModule, 'subModule')->inactive()->create(['name' => ['en' => 'Inactive App']]);
    Application::factory()
        ->for($subModule, 'subModule')
        ->requiringPermission('manage_restricted_app')
        ->create(['name' => ['en' => 'Restricted App']]);

    forgetNavigationTree();

    $this->get('/en/general')
        ->assertSuccessful()
        ->assertSee('1 application')
        ->assertDontSee('3 applications')
        ->assertDontSee('Restricted App')
        ->assertDontSee('Inactive App');
});

it('returns 404 when no active module matches the current route', function (): void {
    Module::factory()->create(['code' => 'gen', 'route' => 'general']);

    forgetNavigationTree();

    $this->get('/en/finance')->assertNotFound();
});

it('returns 404 when the matching module is inactive', function (): void {
    Module::factory()->inactive()->create(['code' => 'gen', 'route' => 'general']);

    forgetNavigationTree();

    $this->get('/en/general')->assertNotFound();
});

it('renders the empty state for a module without sub modules', function (): void {
    Module::factory()->create(['code' => 'crm', 'route' => 'crm']);

    forgetNavigationTree();

    $this->get('/en/crm')
        ->assertSuccessful()
        ->assertSee(__('messages.workspace.empty_title'));
});

it('renders a sub module without a registered route as unavailable instead of a dead link', function (): void {
    $module = Module::factory()->create(['code' => 'gen', 'route' => 'general']);

    SubModule::factory()->for($module)->create([
        'name' => ['en' => 'System Settings'],
        'route' => 'general.system',
    ]);

    forgetNavigationTree();

    $this->get('/en/general')
        ->assertSuccessful()
        ->assertSee(__('messages.workspace.unavailable'))
        ->assertDontSee('href="#"', escape: false);
});

it('links a sub module whose route is registered', function (): void {
    $module = Module::factory()->create(['code' => 'gen', 'route' => 'general']);

    SubModule::factory()->for($module)->create([
        'name' => ['en' => 'Linked SubModule'],
        'route' => 'launcher',
    ]);

    forgetNavigationTree();

    $this->get('/en/general')
        ->assertSuccessful()
        ->assertSee(__('messages.workspace.open'))
        ->assertSee('href="'.route('launcher').'"', escape: false);
});

it('keeps the main launcher route working', function (): void {
    Module::factory()->create([
        'code' => 'gen',
        'route' => 'general',
        'name' => ['en' => 'General Module'],
    ]);

    forgetNavigationTree();

    $this->get('/')
        ->assertSuccessful()
        ->assertSee('General Module');
});
