<?php

use App\Models\User;
use Modules\General\System\Application;
use Modules\HR\Models\EmployeeManagement\Employee;
use Modules\HR\Models\OrganizationStructure\Branch;
use Modules\HR\Models\OrganizationStructure\Department;
use Modules\HR\Models\OrganizationStructure\Entity;
use Modules\HR\Models\OrganizationStructure\JobGrade;
use Modules\HR\Models\OrganizationStructure\JobTitle;
use Spatie\Permission\Models\Role;

/**
 * RefreshDatabase runs migrations only, not the seeders that normally
 * populate the Application rows every screen's access check depends on
 * (NavigationTreeService::getApplicationByCode) — so each APPLICATION_CODE
 * this test suite touches needs a matching row created here.
 */
beforeEach(function () {
    foreach ([
        Entity::APPLICATION_CODE,
        Branch::APPLICATION_CODE,
        Department::APPLICATION_CODE,
        JobGrade::APPLICATION_CODE,
        JobTitle::APPLICATION_CODE,
        Employee::APPLICATION_CODE,
    ] as $code) {
        Application::factory()->create(['code' => $code]);
    }
});

/**
 * Smoke test: every HR screen actually renders (200, no exception) for a
 * super admin, and the record-view pages resolve a real record. This is the
 * "did we wire the routes/registries/views correctly" check the interactive
 * browser session couldn't complete — cheap insurance against a typo'd
 * class reference or an unregistered view key blowing up at request time.
 */
function actingAsSuperAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole(Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']));

    return $admin;
}

test('entities index and show pages render', function () {
    $admin = actingAsSuperAdmin();
    $entity = Entity::factory()->create();

    $this->actingAs($admin)->get(route('hr.organization-structure.entities'))->assertOk();
    $this->actingAs($admin)->get(route('hr.organization-structure.entities.show', $entity->id))->assertOk();
});

test('branches index and show pages render', function () {
    $admin = actingAsSuperAdmin();
    $entity = Entity::factory()->create();
    $branch = Branch::factory()->main()->for($entity)->create();

    $this->actingAs($admin)->get(route('hr.organization-structure.branches'))->assertOk();
    $this->actingAs($admin)->get(route('hr.organization-structure.branches.show', $branch->id))->assertOk();
});

test('departments index and show pages render, including the attached-entities tab', function () {
    $admin = actingAsSuperAdmin();
    $entity = Entity::factory()->create();
    $department = Department::factory()->create();
    $department->attachToEntity($entity);

    $this->actingAs($admin)->get(route('hr.organization-structure.departments'))->assertOk();
    $this->actingAs($admin)->get(route('hr.organization-structure.departments.show', $department->id))->assertOk();
});

test('job grades index and show pages render', function () {
    $admin = actingAsSuperAdmin();
    $grade = JobGrade::factory()->create();

    $this->actingAs($admin)->get(route('hr.organization-structure.job-grades'))->assertOk();
    $this->actingAs($admin)->get(route('hr.organization-structure.job-grades.show', $grade->id))->assertOk();
});

test('job titles index and show pages render, including the grades tab', function () {
    $admin = actingAsSuperAdmin();
    $department = Department::factory()->create();
    $jobTitle = JobTitle::factory()->for($department)->create();
    $grade = JobGrade::factory()->create();
    $jobTitle->jobGrades()->attach($grade->id);

    $this->actingAs($admin)->get(route('hr.organization-structure.job-titles'))->assertOk();
    $this->actingAs($admin)->get(route('hr.organization-structure.job-titles.show', $jobTitle->id))->assertOk();
});

test('employees index and show pages render', function () {
    $admin = actingAsSuperAdmin();
    $employee = Employee::factory()->create();

    $this->actingAs($admin)->get(route('hr.employee-management.employees'))->assertOk();
    $this->actingAs($admin)->get(route('hr.employee-management.employees.show', $employee->id))->assertOk();
});

test('a user with no grant to a permission-gated application is blocked from it', function () {
    // Overrides this suite's beforeEach() default (permission_name null,
    // meaning "any authenticated user") to actually exercise the gate.
    Application::where('code', Entity::APPLICATION_CODE)->update(['permission_name' => 'hr-org-ent.view']);

    $user = User::factory()->create();
    $entity = Entity::factory()->create();

    $this->actingAs($user)->get(route('hr.organization-structure.entities'))->assertNotFound();
    $this->actingAs($user)->get(route('hr.organization-structure.entities.show', $entity->id))->assertNotFound();
});
