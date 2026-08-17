<?php

use App\Models\User;
use Carbon\CarbonImmutable;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\Finance\Models\GeneralLedger\AccountGroup;
use Modules\Finance\Models\GeneralLedger\AccountGroupAssignment;
use Modules\Finance\Models\GeneralLedger\Chart;
use Modules\Finance\Models\GeneralLedger\CostCenter;
use Modules\Finance\Models\GeneralLedger\FiscalYear;
use Modules\Finance\Models\GeneralLedger\Journal;
use Modules\Finance\Models\GeneralLedger\JournalBook;
use Modules\Finance\Models\GeneralLedger\JournalStatus;
use Modules\Finance\Models\GeneralLedger\Ledger;
use Modules\Finance\Services\GeneralLedger\AccountAccessResolver;
use Modules\Finance\Services\GeneralLedger\JournalPoster;
use Modules\Finance\Services\GeneralLedger\TrialBalance;
use Modules\General\Models\World\Currency;
use Modules\HR\Models\EmployeeManagement\Employee;

/**
 * A ledger with a chart holding two postable accounts, a fiscal year split
 * into months, and a journal book — the minimum a journal needs to exist.
 */
function glFixture(): array
{
    $chart = Chart::factory()->create(['levels_count' => 5]);
    $cash = Account::factory()->create(['name' => 'Cash', 'number' => '110101']);
    $sales = Account::factory()->create(['name' => 'Sales', 'number' => '410101']);
    $chart->attachAccount($cash);
    $chart->attachAccount($sales);

    $ledger = Ledger::factory()->create(['chart_id' => $chart->id]);
    $year = FiscalYear::factory()
        ->startingOn(CarbonImmutable::create(2026, 1, 1))
        ->create(['entity_id' => $ledger->entity_id]);
    $period = $year->generatePeriods(12)[0];
    $book = JournalBook::factory()->create(['sequence_prefix' => 'JV']);

    return compact('chart', 'cash', 'sales', 'ledger', 'year', 'period', 'book');
}

function balancedJournal(array $fx, string $amount = '1000'): Journal
{
    $journal = Journal::factory()->create([
        'ledger_id' => $fx['ledger']->id,
        'journal_book_id' => $fx['book']->id,
        'fiscal_period_id' => $fx['period']->id,
        'journal_date' => $fx['period']->start_date,
    ]);

    $currency = Currency::factory()->create();

    $journal->lines()->create([
        'line_number' => 1, 'account_id' => $fx['cash']->id, 'currency_id' => $currency->id,
        'exchange_rate' => 1, 'debit' => $amount, 'base_debit' => $amount, 'credit' => 0, 'base_credit' => 0,
    ]);
    $journal->lines()->create([
        'line_number' => 2, 'account_id' => $fx['sales']->id, 'currency_id' => $currency->id,
        'exchange_rate' => 1, 'credit' => $amount, 'base_credit' => $amount, 'debit' => 0, 'base_debit' => 0,
    ]);

    return $journal->refresh();
}

it('posts a balanced journal and numbers it from its book', function () {
    $fx = glFixture();
    $journal = balancedJournal($fx);

    app(JournalPoster::class)->post($journal);

    expect($journal->status)->toBe(JournalStatus::Posted)
        ->and($journal->number)->toBe('JV-2026-0001')
        ->and((float) $journal->total_debit)->toBe(1000.0)
        ->and((float) $journal->total_credit)->toBe(1000.0)
        ->and($journal->posted_at)->not->toBeNull();
});

it('numbers journals sequentially within the same book and year', function () {
    $fx = glFixture();
    $poster = app(JournalPoster::class);

    $first = $poster->post(balancedJournal($fx));
    $second = $poster->post(balancedJournal($fx));

    expect($first->number)->toBe('JV-2026-0001')
        ->and($second->number)->toBe('JV-2026-0002');
});

it('refuses an unbalanced journal', function () {
    $fx = glFixture();
    $journal = balancedJournal($fx);
    $journal->lines()->where('line_number', 2)->update(['credit' => '900', 'base_credit' => '900']);

    expect(fn () => app(JournalPoster::class)->post($journal->refresh()))
        ->toThrow(RuntimeException::class, 'does not balance');
});

it('refuses a journal with fewer than two lines', function () {
    $fx = glFixture();
    $journal = balancedJournal($fx);
    $journal->lines()->where('line_number', 2)->delete();

    expect(fn () => app(JournalPoster::class)->post($journal->refresh()))
        ->toThrow(RuntimeException::class, 'at least two lines');
});

it('refuses to post into a closed period', function () {
    $fx = glFixture();
    $journal = balancedJournal($fx);
    $fx['ledger']->closePeriod($fx['period']);

    expect(fn () => app(JournalPoster::class)->post($journal))
        ->toThrow(RuntimeException::class, 'not open in ledger');
});

it('refuses a journal dated outside its period', function () {
    $fx = glFixture();
    $journal = balancedJournal($fx);
    $journal->forceFill(['journal_date' => CarbonImmutable::create(2026, 7, 1)])->save();

    expect(fn () => app(JournalPoster::class)->post($journal->refresh()))
        ->toThrow(RuntimeException::class, 'falls outside period');
});

it('refuses posting to a grouping account', function () {
    $fx = glFixture();
    $child = Account::factory()->childOf($fx['cash'])->create();
    $fx['chart']->attachAccount($child);

    $journal = balancedJournal($fx);

    expect($fx['cash']->fresh()->is_postable)->toBeFalse()
        ->and(fn () => app(JournalPoster::class)->post($journal))
        ->toThrow(RuntimeException::class, 'grouping account');
});

it('refuses an account that is not in the ledger chart', function () {
    $fx = glFixture();
    $journal = balancedJournal($fx);

    $fx['chart']->accounts()->detach($fx['sales']->id);

    expect(fn () => app(JournalPoster::class)->post($journal))
        ->toThrow(RuntimeException::class, 'not part of chart');
});

it('refuses an inactive account', function () {
    $fx = glFixture();
    $journal = balancedJournal($fx);
    $fx['sales']->forceFill(['is_active' => false])->save();

    expect(fn () => app(JournalPoster::class)->post($journal))
        ->toThrow(RuntimeException::class, 'is inactive');
});

it('requires the declared analysis type on a control account', function () {
    $fx = glFixture();
    $fx['sales']->forceFill(['required_analysis_type' => 'supplier'])->save();
    $journal = balancedJournal($fx);

    expect(fn () => app(JournalPoster::class)->post($journal))
        ->toThrow(RuntimeException::class, 'requires an analysis of type');
});

it('accepts a control account line that carries the right analysis', function () {
    $fx = glFixture();
    $fx['sales']->forceFill(['required_analysis_type' => 'supplier'])->save();
    $journal = balancedJournal($fx);

    $journal->lines()->where('line_number', 2)->update([
        'analysis_type' => 'supplier',
        'analysis_id' => 7,
        'analysis_code' => 'SUP-0007',
        'analysis_name' => 'Nile Trading',
    ]);

    app(JournalPoster::class)->post($journal->refresh());

    expect($journal->status)->toBe(JournalStatus::Posted);
});

it('never lets a line be both a debit and a credit', function () {
    $fx = glFixture();
    $journal = balancedJournal($fx);
    $line = $journal->lines()->first();

    expect(fn () => $line->update(['debit' => '10', 'credit' => '10']))
        ->toThrow(RuntimeException::class, 'debit and a credit');
});

it('never lets a line carry a negative amount', function () {
    $fx = glFixture();
    $journal = balancedJournal($fx);
    $line = $journal->lines()->first();

    expect(fn () => $line->update(['debit' => '-10']))
        ->toThrow(RuntimeException::class, 'negative amount');
});

it('refuses to edit or delete a posted journal', function () {
    $fx = glFixture();
    $journal = app(JournalPoster::class)->post(balancedJournal($fx));

    expect(fn () => $journal->update(['description' => 'tampered']))
        ->toThrow(RuntimeException::class, 'cannot be modified')
        ->and(fn () => $journal->delete())
        ->toThrow(RuntimeException::class, 'cannot be deleted');
});

it('refuses to post the same journal twice', function () {
    $fx = glFixture();
    $journal = app(JournalPoster::class)->post(balancedJournal($fx));

    expect(fn () => app(JournalPoster::class)->post($journal))
        ->toThrow(RuntimeException::class, 'already posted');
});

it('reverses a posted journal into a mirror that cancels it', function () {
    $fx = glFixture();
    $journal = app(JournalPoster::class)->post(balancedJournal($fx));

    $reversal = app(JournalPoster::class)->reverse($journal);

    expect($journal->fresh()->status)->toBe(JournalStatus::Reversed)
        ->and($reversal->status)->toBe(JournalStatus::Posted)
        ->and($reversal->reverses_journal_id)->toBe($journal->id)
        ->and($reversal->number)->toBe('JV-2026-0002');

    $originalDebitLine = $journal->lines()->where('line_number', 1)->first();
    $reversedLine = $reversal->lines()->where('line_number', 1)->first();

    expect((float) $originalDebitLine->base_debit)->toBe(1000.0)
        ->and((float) $reversedLine->base_credit)->toBe(1000.0)
        ->and((float) $reversedLine->base_debit)->toBe(0.0);
});

it('carries the cost centre across to the reversal', function () {
    $fx = glFixture();
    $centre = CostCenter::factory()->create();

    $journal = balancedJournal($fx);
    $journal->lines()->update(['cost_center_id' => $centre->id]);

    $reversal = app(JournalPoster::class)->reverse(
        app(JournalPoster::class)->post($journal->refresh())
    );

    expect($reversal->lines->pluck('cost_center_id')->unique()->all())->toBe([$centre->id]);
});

it('refuses to reverse a draft or an already reversed journal', function () {
    $fx = glFixture();
    $draft = balancedJournal($fx);

    expect(fn () => app(JournalPoster::class)->reverse($draft))
        ->toThrow(RuntimeException::class, 'Only a posted journal');

    $posted = app(JournalPoster::class)->post(balancedJournal($fx));
    app(JournalPoster::class)->reverse($posted);

    expect(fn () => app(JournalPoster::class)->reverse($posted->fresh()))
        ->toThrow(RuntimeException::class, 'Only a posted journal');
});

it('carries the source reference and line analysis onto the reversal', function () {
    $fx = glFixture();
    $journal = balancedJournal($fx);
    $journal->forceFill(['source_reference' => 'PI-2026-0042'])->save();
    $journal->lines()->where('line_number', 2)->update([
        'analysis_type' => 'supplier',
        'analysis_code' => 'SUP-0007',
        'analysis_name' => 'Nile Trading',
    ]);

    $reversal = app(JournalPoster::class)->reverse(
        app(JournalPoster::class)->post($journal->refresh())
    );

    expect($reversal->source_reference)->toBe('PI-2026-0042')
        ->and($reversal->lines()->where('line_number', 2)->value('analysis_name'))->toBe('Nile Trading');
});

it('builds a trial balance that balances and ignores drafts', function () {
    $fx = glFixture();
    app(JournalPoster::class)->post(balancedJournal($fx, '1000'));
    app(JournalPoster::class)->post(balancedJournal($fx, '250'));
    balancedJournal($fx, '999');

    $trialBalance = app(TrialBalance::class);
    $from = CarbonImmutable::create(2026, 1, 1);
    $to = CarbonImmutable::create(2026, 12, 31);

    $rows = $trialBalance->for($fx['ledger'], $from, $to);

    expect($rows)->toHaveCount(2)
        ->and((float) $rows->firstWhere('number', '110101')->debit)->toBe(1250.0)
        ->and((float) $rows->firstWhere('number', '410101')->credit)->toBe(1250.0)
        ->and($trialBalance->isBalanced($fx['ledger'], $from, $to))->toBeTrue();
});

it('shows a reversal alongside its original in the trial balance', function () {
    $fx = glFixture();
    $journal = app(JournalPoster::class)->post(balancedJournal($fx, '1000'));
    app(JournalPoster::class)->reverse($journal);

    $rows = app(TrialBalance::class)->for(
        $fx['ledger'],
        CarbonImmutable::create(2026, 1, 1),
        CarbonImmutable::create(2026, 12, 31),
    );

    $cash = $rows->firstWhere('number', '110101');

    // Both movements are on the record and they cancel out — the net is zero
    // without either document having been erased.
    expect((float) $cash->debit)->toBe(1000.0)
        ->and((float) $cash->credit)->toBe(1000.0);
});

it('refuses posting to a parent cost centre', function () {
    $fx = glFixture();
    $parent = CostCenter::factory()->create(['name' => 'Operations', 'number' => 'CC-100']);
    CostCenter::factory()->childOf($parent)->create();

    $journal = balancedJournal($fx);
    $journal->lines()->where('line_number', 1)->update(['cost_center_id' => $parent->id]);

    expect($parent->fresh()->is_postable)->toBeFalse()
        ->and(fn () => app(JournalPoster::class)->post($journal->refresh()))
        ->toThrow(RuntimeException::class, 'accepts no transactions');
});

it('refuses a leaf cost centre that was switched off', function () {
    $fx = glFixture();
    $retired = CostCenter::factory()->create(['accepts_transactions' => false]);

    $journal = balancedJournal($fx);
    $journal->lines()->where('line_number', 1)->update(['cost_center_id' => $retired->id]);

    expect(fn () => app(JournalPoster::class)->post($journal->refresh()))
        ->toThrow(RuntimeException::class, 'accepts no transactions');
});

it('posts happily against a leaf cost centre', function () {
    $fx = glFixture();
    $centre = CostCenter::factory()->create();

    $journal = balancedJournal($fx);
    $journal->lines()->update(['cost_center_id' => $centre->id]);

    app(JournalPoster::class)->post($journal->refresh());

    expect($journal->status)->toBe(JournalStatus::Posted);
});

it('withholds a journal whose accounts are not all visible', function () {
    $fx = glFixture();
    $journal = app(JournalPoster::class)->post(balancedJournal($fx));

    $employee = Employee::factory()->create(['user_id' => User::factory()->create()->id]);
    $group = AccountGroup::factory()->create();
    // Only one of the two accounts the journal touches.
    $group->accounts()->attach($fx['cash']->id);
    AccountGroupAssignment::create([
        'account_group_id' => $group->id,
        'assignable_type' => $employee->getMorphClass(),
        'assignable_id' => $employee->getKey(),
    ]);

    $resolver = app(AccountAccessResolver::class);

    expect($resolver->canSeeAllAccountsOf($journal, $employee->user))->toBeFalse();

    $group->accounts()->attach($fx['sales']->id);
    $resolver->flush();

    expect($resolver->canSeeAllAccountsOf($journal, $employee->user))->toBeTrue();
});

it('shows every journal to an unrestricted user', function () {
    $fx = glFixture();
    $journal = app(JournalPoster::class)->post(balancedJournal($fx));

    expect(app(AccountAccessResolver::class)->canSeeAllAccountsOf($journal, User::factory()->create()))->toBeTrue();
});
