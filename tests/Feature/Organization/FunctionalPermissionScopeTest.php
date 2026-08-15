<?php

use App\Models\User;
use App\Services\NavigationTreeService;
use App\Support\Organization\EmployeePermissionSynchronizer;
use Illuminate\Support\Facades\DB;
use Modules\General\System\Module;
use Modules\HR\Models\EmployeeManagement\Employee;
use Modules\HR\Models\OrganizationStructure\Department;
use Modules\HR\Models\OrganizationStructure\JobGrade;
use Modules\HR\Models\OrganizationStructure\JobTitle;
use Spatie\Permission\Models\Permission;

/**
 * Scenario 6: a wide DATA scope (which rows you can see) has zero bearing
 * on FUNCTIONAL access (which screens you can open) — the two engines are
 * independent, and this is the test that would fail first if someone
 * "simplified" them into one.
 */
test('a sales manager with maximal data scope is still denied a finance permission with no grant', function () {
    $sales = Department::factory()->create(['name' => 'Sales']);
    $salesManager = JobTitle::factory()->for($sales)->create(['name' => 'Sales Manager']);
    $grade = JobGrade::factory()->create();
    $salesManager->jobGrades()->attach($grade->id);

    // fin-gl-jou.view exists as a permission (e.g. synced from Finance's
    // applications elsewhere) but nothing grants it to Sales.
    Permission::firstOrCreate(['name' => 'fin-gl-jou.view', 'guard_name' => 'web']);

    $user = User::factory()->create();
    Employee::factory()->create([
        'user_id' => $user->id,
        'department_id' => $sales->id,
        'job_title_id' => $salesManager->id,
        'job_grade_id' => $grade->id,
        // Maximal data scope on purpose — the point under test is that
        // this has no effect on the functional check below.
        'entity_scope' => 'entity_tree',
        'department_scope' => 'department_tree',
    ]);

    expect($user->fresh()->can('fin-gl-jou.view'))->toBeFalse();
});

test('the Finance module is absent from the navigation tree for a user with no grant to it', function () {
    $financeModule = Module::factory()->create(['code' => 'fin', 'permission_name' => 'fin.view', 'sort_order' => 0]);
    Permission::firstOrCreate(['name' => 'fin.view', 'guard_name' => 'web']);

    $sales = Department::factory()->create(['name' => 'Sales']);
    $salesManager = JobTitle::factory()->for($sales)->create(['name' => 'Sales Manager']);
    $grade = JobGrade::factory()->create();
    $salesManager->jobGrades()->attach($grade->id);

    $user = User::factory()->create();
    Employee::factory()->create([
        'user_id' => $user->id,
        'department_id' => $sales->id,
        'job_title_id' => $salesManager->id,
        'job_grade_id' => $grade->id,
        'entity_scope' => 'entity_tree',
        'department_scope' => 'department_tree',
    ]);

    $this->actingAs($user);
    $moduleCodes = collect(app(NavigationTreeService::class)->getTreeForUser())->pluck('code');

    expect($moduleCodes)->not->toContain($financeModule->code);
});

test('granting the department the finance permission flips can() without touching the employee row', function () {
    $sales = Department::factory()->create(['name' => 'Sales']);
    $salesManager = JobTitle::factory()->for($sales)->create(['name' => 'Sales Manager']);
    $grade = JobGrade::factory()->create();
    $salesManager->jobGrades()->attach($grade->id);

    $permission = Permission::firstOrCreate(['name' => 'fin-gl-jou.view', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'department_id' => $sales->id,
        'job_title_id' => $salesManager->id,
        'job_grade_id' => $grade->id,
    ]);

    expect($user->fresh()->can('fin-gl-jou.view'))->toBeFalse();

    // Simulates an admin editing grants directly, independent of the
    // entity/department scope columns on the employee row entirely.
    DB::table('department_permissions')->insert([
        'department_id' => $sales->id,
        'permission_id' => $permission->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    app(EmployeePermissionSynchronizer::class)->sync($employee);

    expect($user->fresh()->can('fin-gl-jou.view'))->toBeTrue();
});

test('a permission-only grant (no role change) still invalidates the cached navigation tree', function () {
    $financeModule = Module::factory()->create(['code' => 'fin', 'permission_name' => 'fin.view', 'sort_order' => 0]);
    $permission = Permission::firstOrCreate(['name' => 'fin.view', 'guard_name' => 'web']);

    $sales = Department::factory()->create(['name' => 'Sales']);
    $salesManager = JobTitle::factory()->for($sales)->create(['name' => 'Sales Manager']);
    $grade = JobGrade::factory()->create();
    $salesManager->jobGrades()->attach($grade->id);

    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'department_id' => $sales->id,
        'job_title_id' => $salesManager->id,
        'job_grade_id' => $grade->id,
    ]);

    $this->actingAs($user);

    // Prime the forever-cached tree BEFORE the grant exists.
    $before = collect(app(NavigationTreeService::class)->getTreeForUser())->pluck('code');
    expect($before)->not->toContain($financeModule->code);

    DB::table('department_permissions')->insert([
        'department_id' => $sales->id,
        'permission_id' => $permission->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    app(EmployeePermissionSynchronizer::class)->sync($employee);

    // Re-authenticate with a fresh model so this asserts the *cache* layer
    // (invalidateCache()), not Eloquent's own in-memory relation
    // memoization on the single $user instance the guard already held —
    // a real request cycle always loads Auth::user() fresh anyway.
    $this->actingAs($user->fresh());

    // No unrelated Module write — only the sync() call above. If
    // invalidateCache() weren't wired into the synchronizer, this would
    // still return the stale $before snapshot even with a fresh user model.
    $after = collect(app(NavigationTreeService::class)->getTreeForUser())->pluck('code');
    expect($after)->toContain($financeModule->code);
});
