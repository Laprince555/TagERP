<?php

use App\Models\Role;
use App\Models\User;
use Modules\General\System\Application;
use Spatie\Permission\Models\Role as SpatieRoleBase;

/**
 * Smoke test: the General → Security & Rules screens (Rules, Permissions
 * catalog) actually render for a super admin.
 */
beforeEach(function () {
    foreach (['gen-sec-per', 'gen-sec-rul'] as $code) {
        Application::factory()->create(['code' => $code]);
    }
});

test('the permissions catalog renders', function () {
    $admin = User::factory()->create();
    $admin->assignRole(SpatieRoleBase::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']));

    $this->actingAs($admin)->get(route('general.security.permissions'))->assertOk();
});

test('rules index and show pages render, including the attached permissions/job-titles/employees tabs', function () {
    $admin = User::factory()->create();
    $admin->assignRole(SpatieRoleBase::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']));

    $rule = Role::create(['name' => 'finance_manager', 'guard_name' => 'web']);

    $this->actingAs($admin)->get(route('general.security.rules'))->assertOk();
    $this->actingAs($admin)->get(route('general.security.rules.show', $rule->id))->assertOk();
});
