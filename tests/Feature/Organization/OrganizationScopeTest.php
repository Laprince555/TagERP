<?php

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Modules\HR\Models\EmployeeManagement\Employee;
use Modules\HR\Models\OrganizationStructure\Branch;
use Modules\HR\Models\OrganizationStructure\Department;
use Modules\HR\Models\OrganizationStructure\Entity;
use Modules\HR\Models\OrganizationStructure\JobGrade;
use Modules\HR\Models\OrganizationStructure\JobTitle;
use Spatie\Permission\Models\Role;

/**
 * Builds A (holding) → B → C, each with a main branch, plus one extra
 * non-main branch on A, and a Finance department (shared, group-wide
 * catalog entry) attached to A — with a Payroll sub-department beneath it,
 * also attached to A. Every test below hangs its employee off this one
 * fixed tree so the assertions read as "given this shape, who sees what".
 *
 * @return array{a: Entity, b: Entity, c: Entity, aMain: Branch, aOther: Branch, finance: Department, payroll: Department}
 */
function makeOrgTree(): array
{
    $a = Entity::factory()->holding()->create(['name' => 'Group A']);
    $b = Entity::factory()->childOf($a)->create(['name' => 'Sub B']);
    $c = Entity::factory()->childOf($b)->create(['name' => 'Sub C']);

    $aMain = Branch::factory()->main()->for($a)->create(['name' => 'A Main']);
    $aOther = Branch::factory()->for($a)->create(['name' => 'A Secondary']);
    Branch::factory()->main()->for($b)->create(['name' => 'B Main']);
    Branch::factory()->main()->for($c)->create(['name' => 'C Main']);

    $finance = Department::factory()->create(['name' => 'Finance']);
    $payroll = Department::factory()->childOf($finance)->create(['name' => 'Payroll']);

    $finance->attachToEntity($a);
    $payroll->attachToEntity($a);

    return compact('a', 'b', 'c', 'aMain', 'aOther', 'finance', 'payroll');
}

/**
 * Fills in a job_title/job_grade pair that actually belongs to the given
 * department_id, unless the caller already supplied one — Employee's
 * assertOrganizationallyConsistent() guard rejects a department/job-title
 * mismatch, and these fixtures only care about entity/branch/department for
 * scope testing, not which job title/grade specifically.
 */
function plantEmployee(array $attributes): Employee
{
    if (isset($attributes['department_id']) && ! isset($attributes['job_title_id'])) {
        $jobTitle = JobTitle::factory()->create(['department_id' => $attributes['department_id']]);
        $jobGrade = JobGrade::factory()->create();
        $jobTitle->jobGrades()->attach($jobGrade->id);

        $attributes['job_title_id'] = $jobTitle->id;
        $attributes['job_grade_id'] = $jobGrade->id;
    }

    return Employee::factory()->create($attributes);
}

function makeEmployeeUser(array $attributes): Employee
{
    $user = User::factory()->create();

    return plantEmployee([...$attributes, 'user_id' => $user->id]);
}

test('entity_tree scope sees the entity and every descendant', function () {
    $tree = makeOrgTree();

    $viewer = makeEmployeeUser([
        'entity_id' => $tree['a']->id,
        'branch_id' => $tree['aMain']->id,
        'department_id' => $tree['finance']->id,
        'entity_scope' => 'entity_tree',
        'department_scope' => 'all',
    ]);

    $inA = plantEmployee(['entity_id' => $tree['a']->id, 'branch_id' => $tree['aMain']->id, 'department_id' => $tree['finance']->id]);
    $inB = plantEmployee(['entity_id' => $tree['b']->id, 'branch_id' => Branch::where('entity_id', $tree['b']->id)->first()->id, 'department_id' => $tree['finance']->id]);
    $inC = plantEmployee(['entity_id' => $tree['c']->id, 'branch_id' => Branch::where('entity_id', $tree['c']->id)->first()->id, 'department_id' => $tree['finance']->id]);

    $this->actingAs($viewer->user);
    $visible = Employee::pluck('id');

    expect($visible)->toContain($viewer->id, $inA->id, $inB->id, $inC->id);
});

test('entity scope (single company) does not see the parent entity', function () {
    $tree = makeOrgTree();

    $viewer = makeEmployeeUser([
        'entity_id' => $tree['b']->id,
        'branch_id' => Branch::where('entity_id', $tree['b']->id)->first()->id,
        'department_id' => $tree['finance']->id,
        'entity_scope' => 'entity',
        'department_scope' => 'all',
    ]);

    $inA = plantEmployee(['entity_id' => $tree['a']->id, 'branch_id' => $tree['aMain']->id, 'department_id' => $tree['finance']->id]);
    $inC = plantEmployee(['entity_id' => $tree['c']->id, 'branch_id' => Branch::where('entity_id', $tree['c']->id)->first()->id, 'department_id' => $tree['finance']->id]);

    $this->actingAs($viewer->user);
    $visible = Employee::pluck('id');

    expect($visible)->toContain($viewer->id)
        ->and($visible)->not->toContain($inA->id)
        ->and($visible)->not->toContain($inC->id);
});

test('branch scope on a non-main branch is confined to that branch, not the whole entity', function () {
    $tree = makeOrgTree();

    $viewer = makeEmployeeUser([
        'entity_id' => $tree['a']->id,
        'branch_id' => $tree['aOther']->id,
        'department_id' => $tree['finance']->id,
        'entity_scope' => 'branch',
        'department_scope' => 'all',
    ]);

    $sameEntityOtherBranch = plantEmployee(['entity_id' => $tree['a']->id, 'branch_id' => $tree['aMain']->id, 'department_id' => $tree['finance']->id]);
    $childEntity = plantEmployee(['entity_id' => $tree['b']->id, 'branch_id' => Branch::where('entity_id', $tree['b']->id)->first()->id, 'department_id' => $tree['finance']->id]);

    $this->actingAs($viewer->user);
    $visible = Employee::pluck('id');

    expect($visible)->toContain($viewer->id)
        ->and($visible)->not->toContain($sameEntityOtherBranch->id)
        ->and($visible)->not->toContain($childEntity->id);
});

test('department_tree scope sees the department and its sub-departments', function () {
    $tree = makeOrgTree();

    $viewer = makeEmployeeUser([
        'entity_id' => $tree['a']->id,
        'branch_id' => $tree['aMain']->id,
        'department_id' => $tree['finance']->id,
        'entity_scope' => 'entity',
        'department_scope' => 'department_tree',
    ]);

    $inFinance = plantEmployee(['entity_id' => $tree['a']->id, 'branch_id' => $tree['aMain']->id, 'department_id' => $tree['finance']->id]);
    $inPayroll = plantEmployee(['entity_id' => $tree['a']->id, 'branch_id' => $tree['aMain']->id, 'department_id' => $tree['payroll']->id]);

    $otherDept = Department::factory()->create(['name' => 'Operations']);
    $otherDept->attachToEntity($tree['a']);
    $inOtherDept = plantEmployee(['entity_id' => $tree['a']->id, 'branch_id' => $tree['aMain']->id, 'department_id' => $otherDept->id]);

    $this->actingAs($viewer->user);
    $visible = Employee::pluck('id');

    expect($visible)->toContain($viewer->id, $inFinance->id, $inPayroll->id)
        ->and($visible)->not->toContain($inOtherDept->id);
});

test('a finance manager in a child entity does not gain scope over the parent entity, even sharing the same department catalog entry', function () {
    $tree = makeOrgTree();

    // The same Finance department row (many-to-many) is also active for B —
    // proving isolation comes from the entity dimension, not from having a
    // separate department id per company.
    $tree['finance']->attachToEntity($tree['b']);

    $viewer = makeEmployeeUser([
        'entity_id' => $tree['b']->id,
        'branch_id' => Branch::where('entity_id', $tree['b']->id)->first()->id,
        'department_id' => $tree['finance']->id,
        'entity_scope' => 'entity',
        'department_scope' => 'department_tree',
    ]);

    $parentFinance = plantEmployee(['entity_id' => $tree['a']->id, 'branch_id' => $tree['aMain']->id, 'department_id' => $tree['finance']->id]);

    $this->actingAs($viewer->user);
    $visible = Employee::pluck('id');

    expect($visible)->not->toContain($parentFinance->id);
});

test('own scope on both dimensions denies visibility into every other record', function () {
    $tree = makeOrgTree();

    $viewer = makeEmployeeUser([
        'entity_id' => $tree['a']->id,
        'branch_id' => $tree['aMain']->id,
        'department_id' => $tree['finance']->id,
        'entity_scope' => 'own',
        'department_scope' => 'own',
    ]);

    $sameBranchColleague = plantEmployee(['entity_id' => $tree['a']->id, 'branch_id' => $tree['aMain']->id, 'department_id' => $tree['finance']->id]);

    $this->actingAs($viewer->user);
    $visible = Employee::pluck('id');

    expect($visible)->not->toContain($sameBranchColleague->id);
});

test('a terminated employee loses visibility immediately, not at next login', function () {
    $tree = makeOrgTree();

    $viewer = makeEmployeeUser([
        'entity_id' => $tree['a']->id,
        'branch_id' => $tree['aMain']->id,
        'department_id' => $tree['finance']->id,
        'entity_scope' => 'entity_tree',
        'department_scope' => 'all',
    ]);

    $peer = plantEmployee(['entity_id' => $tree['b']->id, 'branch_id' => Branch::where('entity_id', $tree['b']->id)->first()->id, 'department_id' => $tree['finance']->id]);

    $this->actingAs($viewer->user);
    expect(Employee::pluck('id'))->toContain($peer->id);

    $viewer->update(['status' => 'terminated']);

    expect(Employee::pluck('id'))->not->toContain($peer->id);
});

test('a user with no active employee record sees nothing', function () {
    $tree = makeOrgTree();
    plantEmployee(['entity_id' => $tree['a']->id, 'branch_id' => $tree['aMain']->id, 'department_id' => $tree['finance']->id]);

    $userWithNoEmployee = User::factory()->create();

    $this->actingAs($userWithNoEmployee);

    expect(Employee::pluck('id'))->toBeEmpty();
});

test('super_admin bypasses organization scope entirely', function () {
    $tree = makeOrgTree();
    $inA = plantEmployee(['entity_id' => $tree['a']->id, 'branch_id' => $tree['aMain']->id, 'department_id' => $tree['finance']->id]);
    $inC = plantEmployee(['entity_id' => $tree['c']->id, 'branch_id' => Branch::where('entity_id', $tree['c']->id)->first()->id, 'department_id' => $tree['finance']->id]);

    $admin = User::factory()->create();
    $admin->assignRole(Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']));

    $this->actingAs($admin);

    expect(Employee::pluck('id'))->toContain($inA->id, $inC->id);
});

test('super_admin bypasses scope even with zero employee record of their own', function () {
    // The bootstrap-deadlock case: proves the role check runs BEFORE the
    // "no active employee → deny all" fallback, not after it.
    $tree = makeOrgTree();
    $inA = plantEmployee(['entity_id' => $tree['a']->id, 'branch_id' => $tree['aMain']->id, 'department_id' => $tree['finance']->id]);

    $admin = User::factory()->create();
    $admin->assignRole(Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']));
    // Deliberately no Employee::factory() row links to $admin at all.

    $this->actingAs($admin);

    expect(Employee::pluck('id'))->toContain($inA->id);
});

test('status=suspended denies scope exactly like terminated, not a partial scope', function () {
    $tree = makeOrgTree();

    $viewer = makeEmployeeUser([
        'entity_id' => $tree['a']->id,
        'branch_id' => $tree['aMain']->id,
        'department_id' => $tree['finance']->id,
        'entity_scope' => 'entity_tree',
        'department_scope' => 'all',
        'status' => 'suspended',
    ]);

    $peer = plantEmployee(['entity_id' => $tree['b']->id, 'branch_id' => Branch::where('entity_id', $tree['b']->id)->first()->id, 'department_id' => $tree['finance']->id]);

    $this->actingAs($viewer->user);

    expect(Employee::pluck('id'))->not->toContain($peer->id)
        ->and(Employee::pluck('id'))->not->toContain($viewer->id);
});

test('an entity_tree employee whose own entity was soft-deleted denies all instead of crashing', function () {
    $tree = makeOrgTree();

    $viewer = makeEmployeeUser([
        'entity_id' => $tree['c']->id,
        'branch_id' => Branch::where('entity_id', $tree['c']->id)->first()->id,
        'department_id' => $tree['finance']->id,
        'entity_scope' => 'entity_tree',
        'department_scope' => 'all',
    ]);

    $tree['c']->delete();

    $this->actingAs($viewer->user);

    // Must resolve to an empty (deny-all), not throw — proves the resolver
    // guards ->entity being null (default belongsTo excludes trashed rows)
    // instead of calling descendantsAndSelf() on null.
    expect(fn () => Employee::pluck('id')->all())->not->toThrow(Throwable::class);
    expect(Employee::pluck('id'))->toBeEmpty();
});

test('reinstating a terminated employee restores visibility on the very next query, no manual cache clear', function () {
    $tree = makeOrgTree();

    $viewer = makeEmployeeUser([
        'entity_id' => $tree['a']->id,
        'branch_id' => $tree['aMain']->id,
        'department_id' => $tree['finance']->id,
        'entity_scope' => 'entity_tree',
        'department_scope' => 'all',
        'status' => 'terminated',
    ]);

    $peer = plantEmployee(['entity_id' => $tree['b']->id, 'branch_id' => Branch::where('entity_id', $tree['b']->id)->first()->id, 'department_id' => $tree['finance']->id]);

    $this->actingAs($viewer->user);
    expect(Employee::pluck('id'))->not->toContain($peer->id);

    $viewer->update(['status' => 'active']);

    expect(Employee::pluck('id'))->toContain($peer->id);
});

test('an entity cannot be moved under one of its own descendants', function () {
    $tree = makeOrgTree();

    expect(fn () => $tree['a']->update(['parent_entity_id' => $tree['c']->id]))
        ->toThrow(ValidationException::class)
        ->and(fn () => $tree['a']->update(['parent_entity_id' => $tree['a']->id]))
        ->toThrow(ValidationException::class);

    // The legitimate move still works: C is not an ancestor of itself's sibling.
    $d = Entity::factory()->childOf($tree['a'])->create(['name' => 'Sub D']);
    $d->update(['parent_entity_id' => $tree['c']->id]);

    expect($d->fresh()->path)->toBe($tree['c']->path.$tree['c']->id.'/');
});

test('a department cannot be moved under one of its own descendants', function () {
    $tree = makeOrgTree();

    expect(fn () => $tree['finance']->update(['parent_department_id' => $tree['payroll']->id]))
        ->toThrow(ValidationException::class);
});
