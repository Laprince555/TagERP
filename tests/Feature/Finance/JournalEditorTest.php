<?php

use App\Livewire\DynamicForm\Form;
use App\Models\User;
use Carbon\CarbonImmutable;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Modules\Finance\Database\Seeders\GeneralLedger\ApplicationsSeeder as FinanceGeneralLedgerApplicationsSeeder;
use Modules\Finance\Database\Seeders\System\SubModulesSeeder as FinanceSubModulesSeeder;
use Modules\Finance\Livewire\GeneralLedger\Journals\JournalEditor;
use Modules\Finance\Livewire\GeneralLedger\Journals\JournalRecordView;
use Modules\Finance\Livewire\GeneralLedger\Journals\JournalsTable;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\Finance\Models\GeneralLedger\Chart;
use Modules\Finance\Models\GeneralLedger\CostCenter;
use Modules\Finance\Models\GeneralLedger\FiscalYear;
use Modules\Finance\Models\GeneralLedger\Journal;
use Modules\Finance\Models\GeneralLedger\JournalBook;
use Modules\Finance\Models\GeneralLedger\JournalStatus;
use Modules\Finance\Models\GeneralLedger\Ledger;
use Modules\General\Database\Seeders\System\ModulesSeeder;
use Modules\General\Database\Seeders\System\SubModulesSeeder;
use Modules\General\Models\World\Currency;
use Spatie\Permission\Models\Role as SpatieRoleBase;

beforeEach(function (): void {
    (new ModulesSeeder)->run();
    (new SubModulesSeeder)->run();
    (new FinanceSubModulesSeeder)->run();
    (new FinanceGeneralLedgerApplicationsSeeder)->run();

    // The record view's action buttons are permission-gated one action each;
    // super_admin short-circuits Gate::before, so these tests exercise the
    // behaviour rather than the grant. The gate itself is tested separately.
    $actor = User::factory()->create();
    $actor->assignRole(SpatieRoleBase::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']));

    $this->actingAs($actor);

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

/**
 * The document now moves through two screens: the grid saves the lines, the
 * record view posts the result. Every test that used to call post() on the
 * editor goes through both.
 */
function saveAndPost(object $test, array $rows): Testable
{
    Livewire::test(JournalEditor::class, ['recordId' => $test->journal->id])
        ->set('rows', $rows)
        ->call('saveDraft');

    return Livewire::test(JournalRecordView::class, ['recordId' => $test->journal->id])
        ->call('runAction', 'post');
}

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
    saveAndPost($this, editorRows($this, '1000', '600'))
        ->assertSet('actionError', fn (?string $error): bool => str_contains((string) $error, 'does not balance'));

    expect($this->journal->fresh()->status)->toBe(JournalStatus::Draft);
});

it('posts once the draft balances', function () {
    saveAndPost($this, editorRows($this, '1000', '1000'))
        ->assertSet('actionError', null);

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

    saveAndPost($this, $rows)->assertSet('actionError', null);

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

    saveAndPost($this, $rows)->assertSet('actionError', null);

    expect($this->journal->fresh()->lines->firstWhere('line_number', 2)->analysis_type)->toBe('supplier');
});

it('leaves a control account unpostable when no counterparty was entered', function () {
    $this->sales->forceFill(['required_analysis_type' => 'supplier'])->save();

    saveAndPost($this, editorRows($this, '1000', '1000'))
        ->assertSet('actionError', fn (?string $error): bool => str_contains((string) $error, 'requires an analysis of type'));
});

it('clears the opposite side when an amount is typed into one of them', function () {
    Livewire::test(JournalEditor::class, ['recordId' => $this->journal->id])
        ->set('rows', editorRows($this, '1000', '1000'))
        ->set('rows.0.credit', '250')
        ->assertSet('rows.0.debit', '');
});

it('will not edit a posted journal', function () {
    $journal = $this->journal;

    saveAndPost($this, editorRows($this, '1000', '1000'));

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
    saveAndPost($this, editorRows($this, '1000', '1000'));

    $reversal = null;

    Livewire::test(JournalRecordView::class, ['recordId' => $this->journal->id])
        ->call('runAction', 'reverse')
        ->assertRedirect(route(
            'finance.general-ledger.journals.show',
            ['recordId' => $reversal = Journal::where('reverses_journal_id', $this->journal->id)->value('id')],
        ));

    expect($this->journal->fresh()->status)->toBe(JournalStatus::Reversed)
        ->and($reversal)->not->toBeNull();
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
        // Posting is the document's action, not the grid's — it lives on the
        // record view now, and the grid is only ever "save what I typed".
        ->assertDontSee('Post');
});

it('leaves the grid read-only once posted, with no actions of its own', function () {
    saveAndPost($this, editorRows($this, '1000', '1000'));

    Livewire::test(JournalEditor::class, ['recordId' => $this->journal->id])
        ->assertOk()
        ->assertSee('A posted journal is never edited.')
        ->assertDontSee('Save draft')
        // The callout still says "Reverse it and enter a corrected one", so
        // the absence to assert is the wired action, not the word.
        ->assertDontSee('wire:click="reverse"', escape: false);
});

/**
 * Someone keeping several companies has to see whose books a journal lands
 * in before keying it. The entity is reached through the ledger, whose eager
 * load is column-narrowed for the record view, so this also guards the
 * silent-null trap that a nested `ledger.entity` read would have hit.
 */
it('shows which entity the journal belongs to', function (): void {
    $entityName = $this->ledger->entity->name;

    Livewire::test(JournalEditor::class, ['recordId' => $this->journal->id])
        ->assertSee($entityName);
});

it('resolves the entity without loading it lazily', function (): void {
    Journal::preventLazyLoading();

    $journal = Journal::with('entity')->find($this->journal->id);

    expect($journal->entity)->not->toBeNull()
        ->and($journal->entity->id)->toBe($this->ledger->entity_id);

    Journal::preventLazyLoading(false);
});

/**
 * The journals list used to jump straight into the line grid, leaving the
 * record view unreachable. The way in is now the record view, and the editor
 * hangs off it as a link.
 */
it('links back to the journal from the line editor, posted or not', function (): void {
    $backLink = route('finance.general-ledger.journals.show', ['recordId' => $this->journal->id]);

    Livewire::test(JournalEditor::class, ['recordId' => $this->journal->id])
        ->assertSee('Back to journal')
        ->assertSee($backLink, escape: false);

    saveAndPost($this, editorRows($this, '1000', '1000'));

    // The read-only grid needs the way out most: every action it used to
    // carry now lives on the record view.
    Livewire::test(JournalEditor::class, ['recordId' => $this->journal->id])
        ->assertSee($backLink, escape: false);
});

it('reaches the line editor through the record view', function (): void {
    Livewire::test(JournalRecordView::class, ['recordId' => $this->journal->id])
        ->assertOk()
        ->assertSee('Edit lines')
        ->assertSee(route('finance.general-ledger.journals.edit', ['recordId' => $this->journal->id]), escape: false);
});

/**
 * Asserted on the wired handler rather than the button's label: "Posted At"
 * is a field on this very page, so assertDontSee('Post') would pass and fail
 * for reasons that have nothing to do with the button.
 */
it('offers the draft actions on the record view and drops them once posted', function (): void {
    Livewire::test(JournalRecordView::class, ['recordId' => $this->journal->id])
        // The trailing quote is what separates the edit modal's event from the
        // copy modal's `…journal.create.copy`.
        ->assertSee("open-form-modal.finance.general-ledger.journal.create'", escape: false)
        ->assertSee(route('finance.general-ledger.journals.edit', ['recordId' => $this->journal->id]), escape: false)
        ->assertSee("runAction('post')", escape: false)
        ->assertSee("runAction('delete')", escape: false)
        ->assertDontSee("runAction('reverse')", escape: false);

    saveAndPost($this, editorRows($this, '1000', '1000'));

    Livewire::test(JournalRecordView::class, ['recordId' => $this->journal->id])
        ->assertSee("runAction('reverse')", escape: false)
        ->assertDontSee("runAction('post')", escape: false)
        ->assertDontSee("runAction('delete')", escape: false)
        ->assertDontSee("open-form-modal.finance.general-ledger.journal.create'", escape: false)
        // Copy survives posting: the header is no longer editable, but it is
        // still a template for the next journal.
        ->assertSee("open-form-modal.finance.general-ledger.journal.create.copy'", escape: false);
});

/**
 * The buttons are one permission each — the whole point of putting them on
 * the engine rather than hard-coding them into the page.
 */
it('hides every action from an actor holding no permission on the application', function (): void {
    $this->actingAs(User::factory()->create());

    Livewire::test(JournalRecordView::class, ['recordId' => $this->journal->id])
        ->assertOk()
        ->assertDontSee('Edit lines')
        ->assertDontSee("runAction('post')", escape: false)
        ->assertDontSee("runAction('delete')", escape: false);
});

it('refuses an action the actor has no permission for, even when the key is forged', function (): void {
    $this->actingAs(User::factory()->create());

    Livewire::test(JournalRecordView::class, ['recordId' => $this->journal->id])
        ->call('runAction', 'post')
        ->assertStatus(404);

    expect($this->journal->fresh()->status)->toBe(JournalStatus::Draft);
});

it('refuses to post through a forged key once the journal is no longer a draft', function (): void {
    saveAndPost($this, editorRows($this, '1000', '1000'));

    Livewire::test(JournalRecordView::class, ['recordId' => $this->journal->id])
        ->call('runAction', 'post')
        ->assertStatus(404);
});

it('deletes a draft from the record view and returns to the list', function (): void {
    Livewire::test(JournalRecordView::class, ['recordId' => $this->journal->id])
        ->call('runAction', 'delete')
        ->assertRedirect(route('finance.general-ledger.journals'));

    expect(Journal::find($this->journal->id))->toBeNull();
});

it('edits the journal header through the record view form', function (): void {
    $book = JournalBook::factory()->create(['sequence_prefix' => 'PV']);

    Livewire::test(Form::class, [
        'formKey' => 'finance.general-ledger.journal.create',
        'recordId' => $this->journal->id,
    ])
        // Prefilled from the record rather than starting empty — that is what
        // separates an edit form from a create form pointed at a record.
        ->assertSet('data.description', $this->journal->description)
        ->set('data.journalBook', $book->id)
        ->set('data.description', 'Corrected narration')
        ->call('save')
        ->assertHasNoErrors();

    $journal = $this->journal->fresh();

    expect($journal->journal_book_id)->toBe($book->id)
        ->and($journal->description)->toBe('Corrected narration');
});

it('copies a journal header into a new draft instead of updating the original', function (): void {
    saveAndPost($this, editorRows($this, '1000', '1000'));

    $before = Journal::count();

    Livewire::test(Form::class, [
        'formKey' => 'finance.general-ledger.journal.create',
        'recordId' => $this->journal->id,
        'copy' => true,
    ])
        // Prefilled from the posted journal — which Edit itself refuses.
        ->assertSet('data.description', $this->journal->description)
        ->assertSet('recordId', null)
        ->set('data.description', 'Next month, same entry')
        ->call('save')
        ->assertHasNoErrors()
        // Lands on the new draft, not on the journal it was copied from.
        ->assertRedirect(route('finance.general-ledger.journals.show', [
            'recordId' => Journal::max('id'),
        ]));

    expect(Journal::count())->toBe($before + 1)
        ->and($this->journal->fresh()->description)->not->toBe('Next month, same entry');

    $copy = Journal::latest('id')->first();

    expect($copy->journal_book_id)->toBe($this->journal->journal_book_id)
        ->and($copy->status)->toBe(JournalStatus::Draft)
        ->and($copy->number)->toBeNull()
        ->and($copy->description)->toBe('Next month, same entry');

    // The lines come with it — a copy of a document is the document.
    $source = $this->journal->fresh();

    expect($copy->lines)->toHaveCount(2)
        ->and($copy->lines->pluck('account_id')->all())->toBe($source->lines->pluck('account_id')->all())
        ->and($copy->total_debit)->toEqual($source->total_debit)
        ->and($copy->total_credit)->toEqual($source->total_credit);
});

it('will not edit the header of a posted journal', function (): void {
    saveAndPost($this, editorRows($this, '1000', '1000'));

    Livewire::test(Form::class, [
        'formKey' => 'finance.general-ledger.journal.create',
        'recordId' => $this->journal->id,
    ])->assertStatus(404);
});

/**
 * The status filter looked applied — chip and all — and returned every row.
 * `multiple` is a boolean attribute, so interpolating it made even a
 * single-choice enum filter a multi-select; the array it then submitted
 * failed single-value normalisation and the whole filter was dropped.
 */
it('narrows the journals list to the statuses ticked in the filter', function (): void {
    saveAndPost($this, editorRows($this, '1000', '1000'));

    $draft = Journal::factory()->create([
        'ledger_id' => $this->ledger->id,
        'journal_book_id' => $this->journal->journal_book_id,
        'fiscal_period_id' => $this->period->id,
        'journal_date' => $this->period->start_date,
    ]);

    Livewire::test(JournalsTable::class)
        ->set('filters.status.value', ['draft'])
        ->call('applyFilters')
        ->assertSee($draft->code)
        ->assertDontSee('JV-2026-0001');

    Livewire::test(JournalsTable::class)
        ->set('filters.status.value', ['posted'])
        ->call('applyFilters')
        ->assertSee('JV-2026-0001')
        ->assertDontSee($draft->code);
});

it('keeps both statuses when both are ticked', function (): void {
    saveAndPost($this, editorRows($this, '1000', '1000'));

    $draft = Journal::factory()->create([
        'ledger_id' => $this->ledger->id,
        'journal_book_id' => $this->journal->journal_book_id,
        'fiscal_period_id' => $this->period->id,
        'journal_date' => $this->period->start_date,
    ]);

    Livewire::test(JournalsTable::class)
        ->set('filters.status.value', ['draft', 'posted'])
        ->call('applyFilters')
        ->assertSee($draft->code)
        ->assertSee('JV-2026-0001');
});

it('sends the journals list to the record view rather than the editor', function (): void {
    Livewire::test(JournalsTable::class)
        ->assertOk()
        ->assertSee(route('finance.general-ledger.journals.show', ['recordId' => $this->journal->id]), escape: false)
        ->assertDontSee(route('finance.general-ledger.journals.edit', ['recordId' => $this->journal->id]), escape: false);
});
