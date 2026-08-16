<?php

use App\Models\User;
use Carbon\CarbonImmutable;
use Livewire\Livewire;
use Modules\Finance\Database\Seeders\GeneralLedger\ApplicationsSeeder as FinanceGeneralLedgerApplicationsSeeder;
use Modules\Finance\Database\Seeders\System\SubModulesSeeder as FinanceSubModulesSeeder;
use Modules\Finance\Livewire\GeneralLedger\Journals\JournalEditor;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\Finance\Models\GeneralLedger\Chart;
use Modules\Finance\Models\GeneralLedger\CostCenter;
use Modules\Finance\Models\GeneralLedger\FiscalYear;
use Modules\Finance\Models\GeneralLedger\Journal;
use Modules\Finance\Models\GeneralLedger\JournalBook;
use Modules\Finance\Models\GeneralLedger\JournalStatus;
use Modules\Finance\Models\GeneralLedger\Ledger;
use Modules\Finance\Services\GeneralLedger\JournalPoster;
use Modules\General\Database\Seeders\System\ModulesSeeder;
use Modules\General\Database\Seeders\System\SubModulesSeeder;
use Modules\General\Models\World\Currency;

beforeEach(function (): void {
    (new ModulesSeeder)->run();
    (new SubModulesSeeder)->run();
    (new FinanceSubModulesSeeder)->run();
    (new FinanceGeneralLedgerApplicationsSeeder)->run();

    $this->actingAs(User::factory()->create());

    $currency = Currency::factory()->create();
    $chart = Chart::factory()->create(['levels_count' => 5]);

    $this->cash = Account::factory()->create(['name' => 'Cash', 'number' => '110101']);
    $this->sales = Account::factory()->create(['name' => 'Sales', 'number' => '410101']);
    $chart->attachAccount($this->cash);
    $chart->attachAccount($this->sales);

    $this->ledger = Ledger::factory()->create([
        'chart_id' => $chart->id,
        'base_currency_id' => $currency->id,
    ]);

    $year = FiscalYear::factory()
        ->startingOn(CarbonImmutable::create(2026, 1, 1))
        ->create(['entity_id' => $this->ledger->entity_id]);

    $this->period = $year->generatePeriods(12)[0];

    $this->journal = Journal::factory()->create([
        'ledger_id' => $this->ledger->id,
        'journal_book_id' => JournalBook::factory()->create(['sequence_prefix' => 'JV'])->id,
        'fiscal_period_id' => $this->period->id,
        'journal_date' => $this->period->start_date,
    ]);
});

function editorRows(object $test, string $debit, string $credit): array
{
    return [
        [
            'id' => null, 'account_id' => $test->cash->id, 'cost_center_id' => null,
            'description' => 'Received from customer', 'analysis_code' => '', 'analysis_name' => '',
            'currency_id' => $test->ledger->base_currency_id, 'exchange_rate' => '1',
            'debit' => $debit, 'credit' => '',
        ],
        [
            'id' => null, 'account_id' => $test->sales->id, 'cost_center_id' => null,
            'description' => 'Revenue', 'analysis_code' => '', 'analysis_name' => '',
            'currency_id' => $test->ledger->base_currency_id, 'exchange_rate' => '1',
            'debit' => '', 'credit' => $credit,
        ],
    ];
}

it('opens with two empty rows on a journal that has none', function () {
    Livewire::test(JournalEditor::class, ['recordId' => $this->journal->id])
        ->assertOk()
        ->assertCount('rows', 2);
});

it('saves an unbalanced draft without complaint', function () {
    Livewire::test(JournalEditor::class, ['recordId' => $this->journal->id])
        ->set('rows', editorRows($this, '1000', '600'))
        ->call('saveDraft')
        ->assertHasNoErrors()
        ->assertSet('postingError', null);

    $journal = $this->journal->fresh();

    // The whole point: an interrupted entry survives, out of balance and all.
    expect($journal->status)->toBe(JournalStatus::Draft)
        ->and($journal->lines)->toHaveCount(2)
        ->and((float) $journal->total_debit)->toBe(1000.0)
        ->and((float) $journal->total_credit)->toBe(600.0);
});

it('refuses to post that same unbalanced draft', function () {
    Livewire::test(JournalEditor::class, ['recordId' => $this->journal->id])
        ->set('rows', editorRows($this, '1000', '600'))
        ->call('post')
        ->assertSet('postingError', fn (?string $error): bool => str_contains((string) $error, 'does not balance'));

    expect($this->journal->fresh()->status)->toBe(JournalStatus::Draft);
});

it('posts once the draft balances', function () {
    Livewire::test(JournalEditor::class, ['recordId' => $this->journal->id])
        ->set('rows', editorRows($this, '1000', '1000'))
        ->call('post')
        ->assertSet('postingError', null);

    $journal = $this->journal->fresh();

    expect($journal->status)->toBe(JournalStatus::Posted)
        ->and($journal->number)->toBe('JV-2026-0001');
});

it('numbers lines in screen order and drops rows the user emptied', function () {
    $rows = editorRows($this, '1000', '1000');
    $rows[] = [
        'id' => null, 'account_id' => null, 'cost_center_id' => null,
        'description' => '', 'analysis_code' => '', 'analysis_name' => '',
        'currency_id' => $this->ledger->base_currency_id, 'exchange_rate' => '1',
        'debit' => '', 'credit' => '',
    ];

    Livewire::test(JournalEditor::class, ['recordId' => $this->journal->id])
        ->set('rows', $rows)
        ->call('saveDraft');

    expect($this->journal->fresh()->lines->pluck('line_number')->all())->toBe([1, 2]);
});

it('converts a foreign-currency line into the ledger base currency', function () {
    $foreign = Currency::factory()->create();
    $rows = editorRows($this, '100', '4850');
    $rows[0]['currency_id'] = $foreign->id;
    $rows[0]['exchange_rate'] = '48.5';

    Livewire::test(JournalEditor::class, ['recordId' => $this->journal->id])
        ->set('rows', $rows)
        ->call('post')
        ->assertSet('postingError', null);

    $line = $this->journal->fresh()->lines->firstWhere('line_number', 1);

    // Entered as 100 foreign, carried into the ledger as 4,850 base.
    expect((float) $line->debit)->toBe(100.0)
        ->and((float) $line->base_debit)->toBe(4850.0)
        ->and($this->journal->fresh()->status)->toBe(JournalStatus::Posted);
});

it('stamps the analysis type from the account when a counterparty is entered', function () {
    $this->sales->forceFill(['required_analysis_type' => 'supplier'])->save();

    $rows = editorRows($this, '1000', '1000');
    $rows[1]['analysis_code'] = 'SUP-0007';
    $rows[1]['analysis_name'] = 'Nile Trading';

    Livewire::test(JournalEditor::class, ['recordId' => $this->journal->id])
        ->set('rows', $rows)
        ->call('post')
        ->assertSet('postingError', null);

    expect($this->journal->fresh()->lines->firstWhere('line_number', 2)->analysis_type)->toBe('supplier');
});

it('leaves a control account unpostable when no counterparty was entered', function () {
    $this->sales->forceFill(['required_analysis_type' => 'supplier'])->save();

    Livewire::test(JournalEditor::class, ['recordId' => $this->journal->id])
        ->set('rows', editorRows($this, '1000', '1000'))
        ->call('post')
        ->assertSet('postingError', fn (?string $error): bool => str_contains((string) $error, 'requires an analysis of type'));
});

it('clears the opposite side when an amount is typed into one of them', function () {
    Livewire::test(JournalEditor::class, ['recordId' => $this->journal->id])
        ->set('rows', editorRows($this, '1000', '1000'))
        ->set('rows.0.credit', '250')
        ->assertSet('rows.0.debit', '');
});

it('will not edit a posted journal', function () {
    $journal = $this->journal;

    Livewire::test(JournalEditor::class, ['recordId' => $journal->id])
        ->set('rows', editorRows($this, '1000', '1000'))
        ->call('post');

    Livewire::test(JournalEditor::class, ['recordId' => $journal->id])
        ->set('header.description', 'tampered')
        ->call('saveDraft')
        ->assertSet('postingError', fn (?string $error): bool => str_contains((string) $error, 'can no longer be edited'));

    expect($journal->fresh()->description)->not->toBe('tampered');
});

it('offers only postable accounts of the ledger chart', function () {
    $outside = Account::factory()->create(['name' => 'Not in chart', 'number' => '999999']);
    $child = Account::factory()->childOf($this->cash)->create();
    Chart::find($this->ledger->chart_id)->attachAccount($child);

    $options = Livewire::test(JournalEditor::class, ['recordId' => $this->journal->id])
        ->instance()
        ->accountOptions()
        ->pluck('id');

    // Cash became a parent, so it drops out; its child and Sales remain.
    expect($options)->toContain($child->id, $this->sales->id)
        ->and($options)->not->toContain($this->cash->id, $outside->id);
});

it('offers only cost centres that accept transactions', function () {
    $parent = CostCenter::factory()->create(['number' => 'CC-100']);
    $leaf = CostCenter::factory()->childOf($parent)->create(['number' => 'CC-110']);
    $retired = CostCenter::factory()->create(['number' => 'CC-200', 'accepts_transactions' => false]);

    $options = Livewire::test(JournalEditor::class, ['recordId' => $this->journal->id])
        ->instance()
        ->costCenterOptions()
        ->pluck('id');

    expect($options)->toContain($leaf->id)
        ->and($options)->not->toContain($parent->id, $retired->id);
});

it('reverses a posted journal and lands on the reversal', function () {
    app(JournalPoster::class)->post(
        tap(Livewire::test(JournalEditor::class, ['recordId' => $this->journal->id])
            ->set('rows', editorRows($this, '1000', '1000'))
            ->call('saveDraft'), fn () => null)
            ->instance()
            ->journal()
    );

    Livewire::test(JournalEditor::class, ['recordId' => $this->journal->id])
        ->call('reverse')
        ->assertRedirect();

    expect($this->journal->fresh()->status)->toBe(JournalStatus::Reversed)
        ->and(Journal::where('reverses_journal_id', $this->journal->id)->count())->toBe(1);
});

it('renders the grid, the account datalist and the keyboard hints', function () {
    Livewire::test(JournalEditor::class, ['recordId' => $this->journal->id])
        ->assertOk()
        ->assertSee('gl-accounts', escape: false)
        ->assertSee('gl-cost-centers', escape: false)
        ->assertSee('data-field="debit"', escape: false)
        ->assertSee('data-field="credit"', escape: false)
        ->assertSee('110101')
        ->assertSee('Save draft')
        ->assertSee('Post');
});

it('shows the reverse action instead of the grid actions once posted', function () {
    Livewire::test(JournalEditor::class, ['recordId' => $this->journal->id])
        ->set('rows', editorRows($this, '1000', '1000'))
        ->call('post');

    Livewire::test(JournalEditor::class, ['recordId' => $this->journal->id])
        ->assertOk()
        ->assertSee('Reverse')
        ->assertSee('A posted journal is never edited.')
        ->assertDontSee('Save draft');
});
