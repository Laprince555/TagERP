<?php

use Database\Seeders\DemoDataSeeder;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\Finance\Models\GeneralLedger\Chart;
use Modules\Finance\Models\GeneralLedger\Journal;
use Modules\Finance\Models\GeneralLedger\Ledger;
use Modules\HR\Models\EmployeeManagement\Employee;
use Modules\HR\Models\OrganizationStructure\Entity;

/**
 * The seeder exists to be run by hand before clicking through the UI, so
 * the only thing worth asserting is that it completes and leaves data that
 * hangs together — every journal balanced, every account reachable from the
 * chart, every employee inside a real org.
 */
it('seeds coherent demo data across every module', function (): void {
    $this->seed(DemoDataSeeder::class);

    // Employee carries a ScopedToOrganization global scope, so an
    // unauthenticated read sees nothing — count the rows, not the view.
    $employees = Employee::withoutGlobalScopes();

    expect(Entity::count())->toBe(3)
        ->and($employees->count())->toBeGreaterThan(0)
        ->and(Ledger::count())->toBe(1)
        ->and(Journal::count())->toBe(36);

    // Every account the seeder made belongs to the one chart.
    expect(Chart::sole()->accounts()->count())->toBe(Account::count());

    // Every employee sits in an entity that actually exists.
    expect(Employee::withoutGlobalScopes()->whereNotIn('entity_id', Entity::pluck('id'))->count())->toBe(0);
});

it('leaves every seeded journal balanced', function (): void {
    $this->seed(DemoDataSeeder::class);

    $unbalanced = Journal::with('lines')->get()->filter(
        fn (Journal $journal): bool => $journal->lines->sum('debit') !== $journal->lines->sum('credit'),
    );

    expect($unbalanced)->toBeEmpty();
});
