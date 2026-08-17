<?php

use Carbon\CarbonImmutable;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\Finance\Models\GeneralLedger\Chart;
use Modules\Finance\Models\GeneralLedger\ExchangeRate;
use Modules\Finance\Models\GeneralLedger\FiscalYear;
use Modules\Finance\Models\GeneralLedger\Journal;
use Modules\Finance\Models\GeneralLedger\JournalBook;
use Modules\Finance\Models\GeneralLedger\JournalStatus;
use Modules\Finance\Models\GeneralLedger\Ledger;
use Modules\Finance\Models\GeneralLedger\LedgerConversionType;
use Modules\Finance\Models\GeneralLedger\RateType;
use Modules\Finance\Services\GeneralLedger\JournalPoster;
use Modules\General\Models\World\Currency;

/**
 * A company keeping two sets of books: the statutory ledger it works in, and a
 * tax ledger in another currency fed from it.
 */
function twoLedgerFixture(bool $sameCurrency = false): array
{
    $egp = Currency::factory()->create(['code' => 'EGP']);
    $usd = $sameCurrency ? $egp : Currency::factory()->create(['code' => 'USD']);

    $chart = Chart::factory()->create(['levels_count' => 5]);
    $cash = Account::factory()->create(['name' => 'Cash', 'number' => '110101']);
    $sales = Account::factory()->create(['name' => 'Sales', 'number' => '410101']);
    $rounding = Account::factory()->create(['name' => 'FX Rounding', 'number' => '590101']);
    $chart->attachAccount($cash);
    $chart->attachAccount($sales);
    $chart->attachAccount($rounding);

    $primary = Ledger::factory()->create([
        'name' => 'Statutory Ledger',
        'chart_id' => $chart->id,
        'base_currency_id' => $egp->id,
    ]);

    $taxChart = Chart::factory()->create(['levels_count' => 5]);
    $taxChart->attachAccount($cash);
    $taxChart->attachAccount($sales);
    $taxChart->attachAccount($rounding);

    $tax = Ledger::factory()->create([
        'name' => 'Tax Ledger',
        'entity_id' => $primary->entity_id,
        'chart_id' => $taxChart->id,
        'base_currency_id' => $usd->id,
        'is_primary' => false,
        'primary_ledger_id' => $primary->id,
        'conversion_type' => $sameCurrency ? LedgerConversionType::Chart : LedgerConversionType::Both,
        'rate_type' => RateType::Daily,
        'rounding_account_id' => $rounding->id,
    ]);

    $year = FiscalYear::factory()
        ->startingOn(CarbonImmutable::create(2026, 1, 1))
        ->create(['entity_id' => $primary->entity_id]);
    $period = $year->generatePeriods(12)[0];

    if (! $sameCurrency) {
        ExchangeRate::factory()->create([
            'from_currency_id' => $egp->id,
            'to_currency_id' => $usd->id,
            'rate_date' => $period->start_date,
            'rate' => '0.02',
            'rate_type' => RateType::Daily,
        ]);
    }

    return compact('primary', 'tax', 'chart', 'taxChart', 'cash', 'sales', 'rounding', 'period', 'egp', 'usd');
}

function journalIn(array $fx, JournalBook $book, string $amount = '1000'): Journal
{
    $journal = Journal::factory()->create([
        'ledger_id' => $fx['primary']->id,
        'journal_book_id' => $book->id,
        'fiscal_period_id' => $fx['period']->id,
        'journal_date' => $fx['period']->start_date,
    ]);

    $journal->lines()->create([
        'line_number' => 1, 'account_id' => $fx['cash']->id, 'currency_id' => $fx['egp']->id,
        'exchange_rate' => 1, 'debit' => $amount, 'base_debit' => $amount, 'credit' => 0, 'base_credit' => 0,
    ]);
    $journal->lines()->create([
        'line_number' => 2, 'account_id' => $fx['sales']->id, 'currency_id' => $fx['egp']->id,
        'exchange_rate' => 1, 'credit' => $amount, 'base_credit' => $amount, 'debit' => 0, 'base_debit' => 0,
    ]);

    return $journal->refresh();
}

it('carries a journal into the tax ledger converted into its currency', function () {
    $fx = twoLedgerFixture();
    $book = JournalBook::factory()->create(['sequence_prefix' => 'JV']);

    $journal = app(JournalPoster::class)->post(journalIn($fx, $book));

    $copy = Journal::where('source_journal_id', $journal->id)->first();

    expect($copy)->not->toBeNull()
        ->and($copy->ledger_id)->toBe($fx['tax']->id)
        ->and($copy->status)->toBe(JournalStatus::Posted)
        ->and((float) $copy->total_debit)->toBe(20.0)
        ->and((float) $copy->total_credit)->toBe(20.0);

    $line = $copy->lines->firstWhere('line_number', 1);

    // Entered as 1,000 EGP; restated as 20 USD at 0.02, with the original
    // amount and currency preserved as the fact of what happened.
    expect((float) $line->debit)->toBe(1000.0)
        ->and($line->currency_id)->toBe($fx['egp']->id)
        ->and((float) $line->base_debit)->toBe(20.0);
});

it('keeps a book out of the tax ledger when it is routed to nothing', function () {
    $fx = twoLedgerFixture();

    // "Management adjustment": stays in the company books, never reaches tax.
    $book = JournalBook::factory()->primaryLedgerOnly()->create(['sequence_prefix' => 'ADJ']);

    $journal = app(JournalPoster::class)->post(journalIn($fx, $book));

    expect($journal->status)->toBe(JournalStatus::Posted)
        ->and(Journal::where('source_journal_id', $journal->id)->count())->toBe(0)
        ->and(Journal::where('ledger_id', $fx['tax']->id)->count())->toBe(0);
});

it('carries a selectively routed book only to the ledgers it names', function () {
    $fx = twoLedgerFixture();
    $book = JournalBook::factory()->primaryLedgerOnly()->create(['sequence_prefix' => 'SEL']);
    $book->ledgers()->attach($fx['tax']->id);

    $journal = app(JournalPoster::class)->post(journalIn($fx, $book));

    expect(Journal::where('source_journal_id', $journal->id)->count())->toBe(1);
});

it('numbers the copy in its own ledger rather than eating the primary sequence', function () {
    $fx = twoLedgerFixture();
    $book = JournalBook::factory()->create(['sequence_prefix' => 'JV']);
    $poster = app(JournalPoster::class);

    $first = $poster->post(journalIn($fx, $book));
    $second = $poster->post(journalIn($fx, $book));

    // The primary's own numbering has no gaps where the copies landed.
    expect($first->number)->toBe('JV-2026-0001')
        ->and($second->number)->toBe('JV-2026-0002')
        ->and(Journal::where('ledger_id', $fx['tax']->id)->orderBy('id')->pluck('number')->all())
        ->toBe(['JV-2026-0001', 'JV-2026-0002']);
});

it('posts a rounding difference when conversion leaves the copy unbalanced', function () {
    $fx = twoLedgerFixture();
    $book = JournalBook::factory()->create(['sequence_prefix' => 'JV']);

    $journal = Journal::factory()->create([
        'ledger_id' => $fx['primary']->id,
        'journal_book_id' => $book->id,
        'fiscal_period_id' => $fx['period']->id,
        'journal_date' => $fx['period']->start_date,
    ]);

    // Three lines whose converted halves cannot land on the same total.
    foreach ([['333.333333', 'debit'], ['333.333333', 'debit'], ['666.666666', 'credit']] as $index => [$amount, $side]) {
        $journal->lines()->create([
            'line_number' => $index + 1,
            'account_id' => $side === 'debit' ? $fx['cash']->id : $fx['sales']->id,
            'currency_id' => $fx['egp']->id,
            'exchange_rate' => 1,
            'debit' => $side === 'debit' ? $amount : 0,
            'base_debit' => $side === 'debit' ? $amount : 0,
            'credit' => $side === 'credit' ? $amount : 0,
            'base_credit' => $side === 'credit' ? $amount : 0,
        ]);
    }

    app(JournalPoster::class)->post($journal->refresh());

    $copy = Journal::where('source_journal_id', $journal->id)->first();

    expect($copy->status)->toBe(JournalStatus::Posted)
        ->and((float) $copy->total_debit)->toBe((float) $copy->total_credit);
});

it('refuses to post when the tax ledger has no rate published', function () {
    $fx = twoLedgerFixture();
    ExchangeRate::query()->delete();
    $book = JournalBook::factory()->create(['sequence_prefix' => 'JV']);
    $journal = journalIn($fx, $book);

    expect(fn () => app(JournalPoster::class)->post($journal))
        ->toThrow(RuntimeException::class, 'No daily exchange rate published');

    // The whole posting is rolled back, so the two ledgers cannot drift apart.
    expect($journal->fresh()->status)->toBe(JournalStatus::Draft)
        ->and(Journal::where('ledger_id', $fx['tax']->id)->count())->toBe(0);
});

it('refuses to post when an account is missing from the tax chart', function () {
    $fx = twoLedgerFixture();
    $fx['taxChart']->accounts()->detach($fx['sales']->id);
    $book = JournalBook::factory()->create(['sequence_prefix' => 'JV']);
    $journal = journalIn($fx, $book);

    expect(fn () => app(JournalPoster::class)->post($journal))
        ->toThrow(RuntimeException::class, 'is missing from chart');

    expect($journal->fresh()->status)->toBe(JournalStatus::Draft);
});

it('refuses to post when the period is closed in the tax ledger only', function () {
    $fx = twoLedgerFixture();
    $fx['tax']->closePeriod($fx['period']);
    $book = JournalBook::factory()->create(['sequence_prefix' => 'JV']);
    $journal = journalIn($fx, $book);

    expect(fn () => app(JournalPoster::class)->post($journal))
        ->toThrow(RuntimeException::class, 'is closed in ledger');
});

it('cancels the copy and produces a reversing copy when the original is reversed', function () {
    $fx = twoLedgerFixture();
    $book = JournalBook::factory()->create(['sequence_prefix' => 'JV']);
    $journal = app(JournalPoster::class)->post(journalIn($fx, $book));
    $copy = Journal::where('source_journal_id', $journal->id)->first();

    $reversal = app(JournalPoster::class)->reverse($journal);

    expect($journal->fresh()->status)->toBe(JournalStatus::Reversed)
        ->and($copy->fresh()->status)->toBe(JournalStatus::Reversed)
        ->and(Journal::where('source_journal_id', $reversal->id)->count())->toBe(1);

    // Net movement in the tax ledger is nil: the copy and its cancellation.
    $taxLines = Journal::where('ledger_id', $fx['tax']->id)->with('lines')->get()
        ->flatMap(fn (Journal $entry) => $entry->lines);

    expect((float) $taxLines->sum('base_debit'))->toBe((float) $taxLines->sum('base_credit'));
});

it('never replicates a copy any further', function () {
    $fx = twoLedgerFixture();
    $book = JournalBook::factory()->create(['sequence_prefix' => 'JV']);
    $journal = app(JournalPoster::class)->post(journalIn($fx, $book));

    expect(Journal::where('ledger_id', $fx['tax']->id)->count())->toBe(1);
});

it('leaves a journal keyed straight into the tax ledger as an original', function () {
    $fx = twoLedgerFixture();
    $book = JournalBook::factory()->create(['sequence_prefix' => 'TAX']);

    $taxOnly = Journal::factory()->create([
        'ledger_id' => $fx['tax']->id,
        'journal_book_id' => $book->id,
        'fiscal_period_id' => $fx['period']->id,
        'journal_date' => $fx['period']->start_date,
    ]);

    foreach ([[$fx['cash']->id, '50', '0'], [$fx['sales']->id, '0', '50']] as $index => [$accountId, $debit, $credit]) {
        $taxOnly->lines()->create([
            'line_number' => $index + 1, 'account_id' => $accountId, 'currency_id' => $fx['usd']->id,
            'exchange_rate' => 1, 'debit' => $debit, 'base_debit' => $debit, 'credit' => $credit, 'base_credit' => $credit,
        ]);
    }

    app(JournalPoster::class)->post($taxOnly->refresh());

    // A tax-only adjustment: editable, posted on its own, copied nowhere.
    expect($taxOnly->fresh()->status)->toBe(JournalStatus::Posted)
        ->and($taxOnly->isGenerated())->toBeFalse()
        ->and(Journal::where('source_journal_id', $taxOnly->id)->count())->toBe(0);
});

it('skips replication entirely when the entity keeps a single ledger', function () {
    $chart = Chart::factory()->create();
    $account = Account::factory()->create(['number' => '110101']);
    $other = Account::factory()->create(['number' => '410101']);
    $chart->attachAccount($account);
    $chart->attachAccount($other);

    $ledger = Ledger::factory()->create(['chart_id' => $chart->id]);
    $year = FiscalYear::factory()->startingOn(CarbonImmutable::create(2026, 1, 1))
        ->create(['entity_id' => $ledger->entity_id]);
    $period = $year->generatePeriods(12)[0];
    $book = JournalBook::factory()->create(['sequence_prefix' => 'JV']);

    $journal = Journal::factory()->create([
        'ledger_id' => $ledger->id,
        'journal_book_id' => $book->id,
        'fiscal_period_id' => $period->id,
        'journal_date' => $period->start_date,
    ]);

    foreach ([[$account->id, '10', '0'], [$other->id, '0', '10']] as $index => [$accountId, $debit, $credit]) {
        $journal->lines()->create([
            'line_number' => $index + 1, 'account_id' => $accountId,
            'currency_id' => $ledger->base_currency_id, 'exchange_rate' => 1,
            'debit' => $debit, 'base_debit' => $debit, 'credit' => $credit, 'base_credit' => $credit,
        ]);
    }

    app(JournalPoster::class)->post($journal->refresh());

    expect(Journal::count())->toBe(1);
});
