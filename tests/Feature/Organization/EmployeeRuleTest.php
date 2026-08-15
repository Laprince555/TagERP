<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\HR\Models\EmployeeManagement\Employee;
use Modules\HR\Models\OrganizationStructure\Department;
use Modules\HR\Models\OrganizationStructure\JobGrade;
use Modules\HR\Models\OrganizationStructure\JobTitle;
use Spatie\Permission\Models\Permission;

/**
 * A Rule (App\Models\Role) assigned directly to one employee — an exception
 * on top of whatever their job title already grants, not a replacement for
 * it and not a bypass of EmployeePermissionSynchronizer as the single writer.
 */
test('a rule granted directly to an employee is applied alongside their job-title-derived roles', function () {
    $department = Department::factory()->create(['name' => 'Sales']);
    $jobTitle = JobTitle::factory()->for($department)->create(['name' => 'Sales Rep']);
    $grade = JobGrade::factory()->create();
    $jobTitle->jobGrades()->attach($grade->id);

    $rule = Role::create(['name' => 'special_project_lead', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'department_id' => $department->id,
        'job_title_id' => $jobTitle->id,
        'job_grade_id' => $grade->id,
    ]);

    DB::table('employee_rules')->insert([
        'employee_id' => $employee->id,
        'role_id' => $rule->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Re-save to trigger the synchronizer (the insert above bypassed it deliberately).
    $employee->touch();

    expect($user->fresh()->hasRole('special_project_lead'))->toBeTrue();
});

test('a direct employee rule does not leak to another employee holding the same job title', function () {
    $department = Department::factory()->create(['name' => 'Sales']);
    $jobTitle = JobTitle::factory()->for($department)->create(['name' => 'Sales Rep']);
    $grade = JobGrade::factory()->create();
    $jobTitle->jobGrades()->attach($grade->id);

    $rule = Role::create(['name' => 'special_project_lead', 'guard_name' => 'web']);

    $leadUser = User::factory()->create();
    $leadEmployee = Employee::factory()->create([
        'user_id' => $leadUser->id,
        'department_id' => $department->id,
        'job_title_id' => $jobTitle->id,
        'job_grade_id' => $grade->id,
    ]);

    $peerUser = User::factory()->create();
    Employee::factory()->create([
        'user_id' => $peerUser->id,
        'department_id' => $department->id,
        'job_title_id' => $jobTitle->id,
        'job_grade_id' => $grade->id,
    ]);

    DB::table('employee_rules')->insert([
        'employee_id' => $leadEmployee->id,
        'role_id' => $rule->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $leadEmployee->touch();

    expect($peerUser->fresh()->hasRole('special_project_lead'))->toBeFalse();
});

test('a rule bundles multiple permissions and grants them all through role assignment', function () {
    $department = Department::factory()->create(['name' => 'Finance']);
    $jobTitle = JobTitle::factory()->for($department)->create(['name' => 'Finance Manager']);
    $grade = JobGrade::factory()->create();
    $jobTitle->jobGrades()->attach($grade->id);

    $rule = Role::create(['name' => 'finance_manager_rule', 'guard_name' => 'web']);
    $rule->givePermissionTo([
        Permission::firstOrCreate(['name' => 'fin-gl-jou.view', 'guard_name' => 'web']),
        Permission::firstOrCreate(['name' => 'fin-ap-inv.view', 'guard_name' => 'web']),
    ]);

    DB::table('job_title_grade_roles')->insert([
        'job_title_id' => $jobTitle->id,
        'job_grade_id' => null,
        'role_id' => $rule->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $user = User::factory()->create();
    Employee::factory()->create([
        'user_id' => $user->id,
        'department_id' => $department->id,
        'job_title_id' => $jobTitle->id,
        'job_grade_id' => $grade->id,
    ]);

    $fresh = $user->fresh();
    expect($fresh->can('fin-gl-jou.view'))->toBeTrue()
        ->and($fresh->can('fin-ap-inv.view'))->toBeTrue();
});
