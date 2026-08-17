<?php

use App\Models\Role;
use App\Models\User;
use Modules\General\System\Application;
use Modules\HR\Models\EmployeeManagement\Employee;
use Spatie\Permission\Models\Role as SpatieRoleBase;

beforeEach(function () {
    Application::factory()->create(['code' => 'gen-sys-usr']);
});

test('the users index and show pages render', function () {
    $admin = superAdmin();
    $admin->assignRole(SpatieRoleBase::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']));

    $target = User::factory()->create();

    $this->actingAs($admin)->get(route('general.system.users'))->assertOk();
    $this->actingAs($admin)->get(route('general.system.users.show', $target->id))->assertOk();
});

test('a user with no employee record is a valid, fully functional admin account', function () {
    $admin = superAdmin();
    $admin->assignRole(SpatieRoleBase::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']));

    expect($admin->employee)->toBeNull()
        ->and($admin->fresh()->hasRole('super_admin'))->toBeTrue();
});

test('User::employee() resolves the linked Employee row, and stays null until one is linked', function () {
    // Employee carries ScopedToOrganization, so resolving it needs a scope
    // to resolve against â€” acting as super_admin bypasses that scope
    // entirely, isolating this assertion to the relation itself.
    $admin = superAdmin();
    $admin->assignRole(Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']));
    $this->actingAs($admin);

    $user = User::factory()->create();
    expect($user->employee)->toBeNull();

    $employee = Employee::factory()->create(['user_id' => $user->id]);

    expect($user->fresh()->employee?->id)->toBe($employee->id);
});
