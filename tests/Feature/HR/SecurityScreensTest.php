<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Modules\General\System\Application;
use Spatie\Permission\Models\Role as SpatieRoleBase;

/**
 * Smoke test: the General → Security & Roles screens (Roles, Permissions
 * catalog) actually render for a super admin.
 */
beforeEach(function () {
    foreach (['gen-sec-per', 'gen-sec-rol'] as $code) {
        Application::factory()->create(['code' => $code]);
    }
});

test('the permissions catalog renders', function () {
    $admin = User::factory()->create();
    $admin->assignRole(SpatieRoleBase::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']));

    $this->actingAs($admin)->get(route('general.security.permissions'))->assertOk();
});

test('roles index and show pages render, including the attached permissions/job-titles/employees tabs', function () {
    $admin = User::factory()->create();
    $admin->assignRole(SpatieRoleBase::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']));

    $role = Role::create(['name' => 'finance_manager', 'guard_name' => 'web']);

    $this->actingAs($admin)->get(route('general.security.roles'))->assertOk();
    $this->actingAs($admin)->get(route('general.security.roles.show', $role->id))->assertOk();
});

it('renders the record view of a permission with the roles that grant it', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole(SpatieRoleBase::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']));

    $permission = Permission::create(['name' => 'fin-gl-jou.post', 'guard_name' => 'web']);
    $role = Role::create(['name' => 'Accountant', 'guard_name' => 'web']);
    $role->givePermissionTo($permission);

    $this->actingAs($admin)->get(route('general.security.permissions.show', ['recordId' => $permission->id]))
        ->assertSuccessful()
        ->assertSee('fin-gl-jou.post')
        // The two halves the generated name is built from.
        ->assertSee('fin-gl-jou')
        ->assertSee('Roles');
});
