<?php

use App\Models\User;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\Finance\Models\GeneralLedger\AccountGroup;
use Modules\Finance\Models\GeneralLedger\AccountGroupAssignment;
use Modules\Finance\Services\GeneralLedger\AccountAccessResolver;
use Modules\Finance\System\GeneralLedger\AccountRecordReferenceProvider;
use Modules\HR\Models\EmployeeManagement\Employee;
use Modules\HR\Models\OrganizationStructure\JobTitle;

/**
 * An employee who actually has a login, which is what the resolver walks from.
 */
function employeeWithUser(): Employee
{
    return Employee::factory()->create(['user_id' => User::factory()->create()->id]);
}

function grantGroup(AccountGroup $group, object $assignable): void
{
    AccountGroupAssignment::create([
        'account_group_id' => $group->id,
        'assignable_type' => $assignable->getMorphClass(),
        'assignable_id' => $assignable->getKey(),
    ]);
}

it('leaves a user with no employee record unrestricted', function () {
    Account::factory()->count(3)->create();
    $user = User::factory()->create();

    expect(app(AccountAccessResolver::class)->visibleAccountIds($user))->toBeNull();
});

it('leaves an employee with no account group unrestricted', function () {
    Account::factory()->count(3)->create();
    $employee = employeeWithUser();

    expect(app(AccountAccessResolver::class)->visibleAccountIds($employee->user))->toBeNull();
});

it('restricts an employee to the accounts of their directly assigned group', function () {
    $visible = Account::factory()->count(2)->create();
    $hidden = Account::factory()->create();

    $employee = employeeWithUser();
    $group = AccountGroup::factory()->create();
    $group->accounts()->attach($visible->pluck('id'));
    grantGroup($group, $employee);

    $resolved = app(AccountAccessResolver::class)->visibleAccountIds($employee->user);

    expect($resolved)->toHaveCount(2)
        ->and($resolved)->toContain(...$visible->pluck('id')->all())
        ->and($resolved)->not->toContain($hidden->id);
});

it('grants a group through the job title as well as directly', function () {
    $byTitle = Account::factory()->create();
    $byPerson = Account::factory()->create();
    Account::factory()->create();

    $employee = employeeWithUser();
    $jobTitle = JobTitle::find($employee->job_title_id);

    $titleGroup = AccountGroup::factory()->create();
    $titleGroup->accounts()->attach($byTitle->id);
    grantGroup($titleGroup, $jobTitle);

    $personGroup = AccountGroup::factory()->create();
    $personGroup->accounts()->attach($byPerson->id);
    grantGroup($personGroup, $employee);

    $resolved = app(AccountAccessResolver::class)->visibleAccountIds($employee->user);

    expect($resolved)->toHaveCount(2)
        ->and($resolved)->toContain($byTitle->id, $byPerson->id);
});

it('ignores template groups when deciding what can be seen', function () {
    $account = Account::factory()->create();
    Account::factory()->create();

    $employee = employeeWithUser();
    $template = AccountGroup::factory()->template()->create();
    $template->accounts()->attach($account->id);
    grantGroup($template, $employee);

    // A template says which accounts belong in a chart, not who may look at
    // them, so it must leave the person unrestricted rather than narrow them.
    expect(app(AccountAccessResolver::class)->visibleAccountIds($employee->user))->toBeNull();
});

it('ignores an inactive group', function () {
    $account = Account::factory()->create();
    $employee = employeeWithUser();
    $group = AccountGroup::factory()->create(['is_active' => false]);
    $group->accounts()->attach($account->id);
    grantGroup($group, $employee);

    expect(app(AccountAccessResolver::class)->visibleAccountIds($employee->user))->toBeNull();
});

it('restricts every account query it is wired into', function () {
    $visible = Account::factory()->create(['name' => 'Visible Account']);
    $hidden = Account::factory()->create(['name' => 'Hidden Account']);

    $employee = employeeWithUser();
    $group = AccountGroup::factory()->create();
    $group->accounts()->attach($visible->id);
    grantGroup($group, $employee);

    $this->actingAs($employee->user);

    $restricted = app(AccountAccessResolver::class)->restrict(Account::query())->pluck('id')->all();
    $referenced = (new AccountRecordReferenceProvider)->scopeQuery(Account::query())->pluck('id')->all();

    expect($restricted)->toBe([$visible->id])
        ->and($referenced)->toBe([$visible->id])
        ->and($referenced)->not->toContain($hidden->id);
});

it('keeps structural questions independent of who is looking', function () {
    $parent = Account::factory()->create();
    $child = Account::factory()->childOf($parent)->create();

    $employee = employeeWithUser();
    $group = AccountGroup::factory()->create();
    $group->accounts()->attach($child->id);
    grantGroup($group, $employee);

    $this->actingAs($employee->user);

    // The restricted user cannot see the parent, but the parent is still a
    // parent: postability and the tree must not change per viewer, or posting
    // would start failing for reasons that have nothing to do with accounting.
    expect($parent->fresh()->is_postable)->toBeFalse()
        ->and($child->fresh()->ancestors())->toHaveCount(1)
        ->and(Account::query()->count())->toBe(2);
});

it('drops the cached answer when an assignment changes', function () {
    $first = Account::factory()->create();
    $second = Account::factory()->create();

    $employee = employeeWithUser();
    $group = AccountGroup::factory()->create();
    $group->accounts()->attach($first->id);
    grantGroup($group, $employee);

    $resolver = app(AccountAccessResolver::class);

    expect($resolver->visibleAccountIds($employee->user))->toBe([$first->id]);

    $widerGroup = AccountGroup::factory()->create();
    $widerGroup->accounts()->attach($second->id);
    grantGroup($widerGroup, $employee);

    expect($resolver->visibleAccountIds($employee->user))
        ->toHaveCount(2)
        ->toContain($first->id, $second->id);
});

it('still restricts a user whose employee row is outside the organization scope', function () {
    $visible = Account::factory()->create();
    Account::factory()->create();

    $employee = employeeWithUser();
    $group = AccountGroup::factory()->create();
    $group->accounts()->attach($visible->id);
    grantGroup($group, $employee);

    // Employee is organization-scoped, so a plain lookup finds nothing here.
    // Identity resolution must not depend on that scope, or the restriction
    // would quietly turn itself off for exactly the people it applies to.
    expect(Employee::where('user_id', $employee->user_id)->count())->toBe(0)
        ->and(app(AccountAccessResolver::class)->visibleAccountIds($employee->user))
        ->toBe([$visible->id]);
});
