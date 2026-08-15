<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\HR\Models\EmployeeManagement\Employee;
use Modules\HR\Models\OrganizationStructure\Department;
use Modules\HR\Models\OrganizationStructure\JobGrade;
use Modules\HR\Models\OrganizationStructure\JobTitle;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * "General Accountant" under Finance, with a junior/senior ladder:
 * everyone in the title can view the general ledger; only senior and above
 * can post to it. Mirrors the exact example from the design conversation.
 */
function makeAccountingLadder(): array
{
    $finance = Department::factory()->create(['name' => 'Finance']);
    $generalAccountant = JobTitle::factory()->for($finance)->create(['name' => 'General Accountant']);

    $junior = JobGrade::factory()->create(['name' => 'Junior', 'level' => 10]);
    $senior = JobGrade::factory()->create(['name' => 'Senior', 'level' => 30]);
    $generalAccountant->jobGrades()->attach([$junior->id, $senior->id]);

    $viewPermission = Permission::firstOrCreate(['name' => 'fin-gl-jou.view', 'guard_name' => 'web']);
    $postPermission = Permission::firstOrCreate(['name' => 'fin-gl-jou.post', 'guard_name' => 'web']);

    DB::table('job_title_permissions')->insert([
        'job_title_id' => $generalAccountant->id,
        'permission_id' => $viewPermission->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('job_title_grade_permissions')->insert([
        'job_title_id' => $generalAccountant->id,
        'job_grade_id' => $senior->id,
        'permission_id' => $postPermission->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return compact('finance', 'generalAccountant', 'junior', 'senior');
}

test('a job title grant applies to every grade holding that title', function () {
    $ladder = makeAccountingLadder();
    $user = User::factory()->create();

    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'department_id' => $ladder['finance']->id,
        'job_title_id' => $ladder['generalAccountant']->id,
        'job_grade_id' => $ladder['junior']->id,
    ]);

    expect($user->fresh()->can('fin-gl-jou.view'))->toBeTrue();
});

test('a grade-gated grant is withheld below its threshold grade', function () {
    $ladder = makeAccountingLadder();
    $user = User::factory()->create();

    Employee::factory()->create([
        'user_id' => $user->id,
        'department_id' => $ladder['finance']->id,
        'job_title_id' => $ladder['generalAccountant']->id,
        'job_grade_id' => $ladder['junior']->id,
    ]);

    expect($user->fresh()->can('fin-gl-jou.post'))->toBeFalse();
});

test('a grade-gated grant is held once the employee reaches that grade', function () {
    $ladder = makeAccountingLadder();
    $user = User::factory()->create();

    Employee::factory()->create([
        'user_id' => $user->id,
        'department_id' => $ladder['finance']->id,
        'job_title_id' => $ladder['generalAccountant']->id,
        'job_grade_id' => $ladder['senior']->id,
    ]);

    expect($user->fresh()->can('fin-gl-jou.post'))->toBeTrue();
});

test('a department grant reaches everyone in that department regardless of job title', function () {
    $finance = Department::factory()->create(['name' => 'Finance']);
    $someTitle = JobTitle::factory()->for($finance)->create();
    $someGrade = JobGrade::factory()->create();
    $someTitle->jobGrades()->attach($someGrade->id);

    $financeModule = Permission::firstOrCreate(['name' => 'fin.view', 'guard_name' => 'web']);
    DB::table('department_permissions')->insert([
        'department_id' => $finance->id,
        'permission_id' => $financeModule->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $user = User::factory()->create();
    Employee::factory()->create([
        'user_id' => $user->id,
        'department_id' => $finance->id,
        'job_title_id' => $someTitle->id,
        'job_grade_id' => $someGrade->id,
    ]);

    expect($user->fresh()->can('fin.view'))->toBeTrue();
});

test('someone outside the department does not gain its module access', function () {
    $finance = Department::factory()->create(['name' => 'Finance']);
    $operations = Department::factory()->create(['name' => 'Operations']);
    $opsTitle = JobTitle::factory()->for($operations)->create();
    $opsGrade = JobGrade::factory()->create();
    $opsTitle->jobGrades()->attach($opsGrade->id);

    $financeModule = Permission::firstOrCreate(['name' => 'fin.view', 'guard_name' => 'web']);
    DB::table('department_permissions')->insert([
        'department_id' => $finance->id,
        'permission_id' => $financeModule->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $user = User::factory()->create();
    Employee::factory()->create([
        'user_id' => $user->id,
        'department_id' => $operations->id,
        'job_title_id' => $opsTitle->id,
        'job_grade_id' => $opsGrade->id,
    ]);

    expect($user->fresh()->can('fin.view'))->toBeFalse();
});

test('a named role attached to a job title is assigned to whoever holds it', function () {
    $finance = Department::factory()->create(['name' => 'Finance']);
    $managerTitle = JobTitle::factory()->for($finance)->create(['name' => 'Finance Manager']);
    $grade = JobGrade::factory()->create();
    $managerTitle->jobGrades()->attach($grade->id);

    $role = Role::firstOrCreate(['name' => 'finance_manager', 'guard_name' => 'web']);
    DB::table('job_title_grade_roles')->insert([
        'job_title_id' => $managerTitle->id,
        'job_grade_id' => null,
        'role_id' => $role->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $user = User::factory()->create();
    Employee::factory()->create([
        'user_id' => $user->id,
        'department_id' => $finance->id,
        'job_title_id' => $managerTitle->id,
        'job_grade_id' => $grade->id,
    ]);

    expect($user->fresh()->hasRole('finance_manager'))->toBeTrue();
});

test('terminating an employee immediately strips their permissions and roles', function () {
    $ladder = makeAccountingLadder();
    $user = User::factory()->create();

    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'department_id' => $ladder['finance']->id,
        'job_title_id' => $ladder['generalAccountant']->id,
        'job_grade_id' => $ladder['senior']->id,
    ]);

    expect($user->fresh()->can('fin-gl-jou.post'))->toBeTrue();

    $employee->update(['status' => 'terminated']);

    expect($user->fresh()->can('fin-gl-jou.post'))->toBeFalse();
});

test('reconcile detects and fixes drift from a grant added after the employee was hired', function () {
    $ladder = makeAccountingLadder();
    $user = User::factory()->create();

    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'department_id' => $ladder['finance']->id,
        'job_title_id' => $ladder['generalAccountant']->id,
        'job_grade_id' => $ladder['senior']->id,
    ]);

    // A new grant is attached directly to the grant table, bypassing the
    // synchronizer entirely — simulating an admin editing grants without
    // saving the employee row, which is exactly what reconcile exists for.
    $exportPermission = Permission::firstOrCreate(['name' => 'fin-gl-jou.export', 'guard_name' => 'web']);
    DB::table('job_title_permissions')->insert([
        'job_title_id' => $ladder['generalAccountant']->id,
        'permission_id' => $exportPermission->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect($user->fresh()->can('fin-gl-jou.export'))->toBeFalse();

    $this->artisan('hr:permissions:reconcile')->assertSuccessful();

    expect($user->fresh()->can('fin-gl-jou.export'))->toBeTrue();
});

test('deleting an employee strips their permissions and roles', function () {
    $ladder = makeAccountingLadder();
    $user = User::factory()->create();

    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'department_id' => $ladder['finance']->id,
        'job_title_id' => $ladder['generalAccountant']->id,
        'job_grade_id' => $ladder['senior']->id,
    ]);

    expect($user->fresh()->can('fin-gl-jou.post'))->toBeTrue();

    $employee->delete();

    expect($user->fresh()->can('fin-gl-jou.post'))->toBeFalse();
});

test('reconcile strips permissions left behind on an employee who is no longer active', function () {
    $ladder = makeAccountingLadder();
    $user = User::factory()->create();

    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'department_id' => $ladder['finance']->id,
        'job_title_id' => $ladder['generalAccountant']->id,
        'job_grade_id' => $ladder['senior']->id,
    ]);

    // Terminated straight in the database, bypassing the model events — the
    // missed-sync case the reconcile backstop exists to catch.
    DB::table('employees')->where('id', $employee->id)->update(['status' => 'terminated']);

    expect($user->fresh()->can('fin-gl-jou.post'))->toBeTrue();

    $this->artisan('hr:permissions:reconcile')->assertSuccessful();

    expect($user->fresh()->can('fin-gl-jou.post'))->toBeFalse();
});
