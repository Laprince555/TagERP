<?php

namespace Modules\Finance\Livewire\GeneralLedger\Journals;

use App\Services\NavigationTreeService;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\Finance\Models\GeneralLedger\CostCenter;
use Modules\Finance\Models\GeneralLedger\Journal;
use Modules\Finance\Services\GeneralLedger\AccountAccessResolver;
use Modules\Finance\Services\GeneralLedger\JournalPoster;
use Modules\General\Models\World\Currency;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The line-entry grid: the one screen in the General Ledger that the dynamic
 * table engine cannot express, because it edits many rows at once and has to
 * total them as they are typed.
 *
 * A draft is saved in whatever state it is in — unbalanced, half-filled, one
 * line — because bookkeeping is interrupted constantly and losing an hour of
 * keying to a validation rule is worse than storing an incomplete document.
 * Every accounting rule is enforced at posting time by JournalPoster instead,
 * which is the only moment the journal has to be true.
 */
#[Layout('layouts.app')]
class JournalEditor extends Component
{
    public int $recordId;

    /** @var array<string, mixed> */
    public array $header = [
        'journal_date' => '',
        'description' => '',
        'source_reference' => '',
    ];

    /**
     * The grid, one entry per line. Kept as a plain array rather than as models
     * so a half-typed row costs nothing until it is saved.
     *
     * @var array<int, array<string, mixed>>
     */
    public array $rows = [];

    public ?string $postingError = null;

    public ?string $flash = null;

    public function mount(int $recordId): void
    {
        $this->recordId = $recordId;

        $journal = $this->journal();

        $this->header = [
            'journal_date' => $journal->journal_date->toDateString(),
            'description' => (string) $journal->description,
            'source_reference' => (string) $journal->source_reference,
        ];

        $this->rows = $journal->lines->map(fn ($line): array => [
            'id' => $line->id,
            'account_id' => $line->account_id,
            'cost_center_id' => $line->cost_center_id,
            'description' => (string) $line->description,
            'analysis_code' => (string) $line->analysis_code,
            'analysis_name' => (string) $line->analysis_name,
            'currency_id' => $line->currency_id,
            'exchange_rate' => (string) $line->exchange_rate,
            'debit' => $this->trimAmount($line->debit),
            'credit' => $this->trimAmount($line->credit),
        ])->all();

        if ($this->rows === []) {
            $this->addRow();
            $this->addRow();
        }
    }

    #[Computed]
    public function journal(): Journal
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Journal::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }

        $journal = Journal::with(['lines', 'ledger.baseCurrency', 'journalBook', 'fiscalPeriod'])
            ->find($this->recordId);

        if ($journal === null) {
            throw new NotFoundHttpException;
        }

        // Withholding the detail of a journal whose accounts are not all
        // visible has to cover editing too, not just the read-only view.
        if (! app(AccountAccessResolver::class)->canSeeAllAccountsOf($journal)) {
            throw new NotFoundHttpException;
        }

        return $journal;
    }

    public function isEditable(): bool
    {
        return $this->journal()->status->isEditable() && ! $this->journal()->isGenerated();
    }

    /**
     * Postable accounts of this journal's chart, narrowed to what the user may
     * see.
     *
     * ponytail: the whole set is handed to a datalist, which the browser
     * filters natively as you type — no JS, no round trip. Swap for a debounced
     * server-side search if a chart ever carries more postable accounts than a
     * datalist stays responsive with (low thousands).
     *
     * @return Collection<int, Account>
     */
    #[Computed]
    public function accountOptions(): Collection
    {
        return app(AccountAccessResolver::class)->restrict(
            Account::query()
                ->where('is_active', true)
                ->whereDoesntHave('children')
                ->whereHas('charts', fn ($chart) => $chart->where('charts.id', $this->journal()->ledger->chart_id))
                ->orderBy('number')
                ->select(['id', 'number', 'name', 'required_analysis_type'])
        )->get();
    }

    /**
     * @return Collection<int, CostCenter>
     */
    #[Computed]
    public function costCenterOptions(): Collection
    {
        return CostCenter::query()
            ->where('is_active', true)
            ->where('accepts_transactions', true)
            ->whereDoesntHave('children')
            ->orderBy('number')
            ->select(['id', 'number', 'name'])
            ->get();
    }

    /**
     * @return Collection<int, Currency>
     */
    #[Computed]
    public function currencyOptions(): Collection
    {
        return Currency::query()->orderBy('code')->select(['id', 'code', 'name'])->get();
    }

    public function addRow(): void
    {
        $this->rows[] = [
            'id' => null,
            'account_id' => null,
            'cost_center_id' => null,
            'description' => '',
            'analysis_code' => '',
            'analysis_name' => '',
            'currency_id' => $this->journal()->ledger->base_currency_id,
            'exchange_rate' => '1',
            'debit' => '',
            'credit' => '',
        ];
    }

    public function removeRow(int $index): void
    {
        unset($this->rows[$index]);

        $this->rows = array_values($this->rows);

        if ($this->rows === []) {
            $this->addRow();
        }
    }

    /**
     * Typing in one side clears the other, so a line can never end up carrying
     * both — the mistake is caught while the cursor is still there rather than
     * as an exception on save.
     */
    public function updated(string $property): void
    {
        if (preg_match('/^rows\.(\d+)\.(debit|credit)$/', $property, $matches) !== 1) {
            return;
        }

        [$index, $side] = [(int) $matches[1], $matches[2]];

        if ((float) ($this->rows[$index][$side] ?? 0) > 0) {
            $this->rows[$index][$side === 'debit' ? 'credit' : 'debit'] = '';
        }
    }

    /**
     * Persist exactly what is on screen, balanced or not.
     */
    public function saveDraft(): void
    {
        $this->postingError = null;

        if (! $this->isEditable()) {
            $this->postingError = __('This journal is posted and can no longer be edited.');

            return;
        }

        $this->validate($this->rules(), [], $this->validationAttributes());

        DB::transaction(function (): void {
            $journal = $this->journal();

            $journal->forceFill([
                'journal_date' => $this->header['journal_date'],
                'description' => $this->header['description'] ?: null,
                'source_reference' => $this->header['source_reference'] ?: null,
            ])->save();

            $keptIds = [];
            $lineNumber = 0;

            foreach ($this->rows as $row) {
                if ($this->isBlankRow($row)) {
                    continue;
                }

                $lineNumber++;
                $line = $journal->lines()->updateOrCreate(
                    ['id' => $row['id']],
                    $this->lineAttributes($row, $lineNumber),
                );

                $keptIds[] = $line->id;
            }

            // Rows the user deleted, and rows they emptied out, both disappear.
            $journal->lines()->whereNotIn('id', $keptIds ?: [0])->delete();

            $journal->forceFill([
                'total_debit' => $journal->lines()->sum('base_debit'),
                'total_credit' => $journal->lines()->sum('base_credit'),
            ])->save();
        });

        unset($this->journal);
        $this->mount($this->recordId);

        $this->flash = __('Draft saved.');
    }

    /**
     * Save first, then hand the finished document to the poster. The poster
     * owns every accounting rule; this screen only reports what it says.
     */
    public function post(): void
    {
        $this->saveDraft();

        if ($this->postingError !== null) {
            return;
        }

        try {
            app(JournalPoster::class)->post($this->journal(), auth()->id());
        } catch (RuntimeException $exception) {
            $this->postingError = $exception->getMessage();
            $this->flash = null;

            return;
        }

        unset($this->journal);

        $this->flash = __('Journal posted.');
    }

    public function reverse(): void
    {
        $this->postingError = null;

        try {
            $reversal = app(JournalPoster::class)->reverse($this->journal(), auth()->id());
        } catch (RuntimeException $exception) {
            $this->postingError = $exception->getMessage();

            return;
        }

        $this->redirectRoute('finance.general-ledger.journals.edit', ['recordId' => $reversal->id], navigate: true);
    }

    /**
     * Totals of what is currently on screen. The grid keeps its own running
     * total in the browser for instant feedback; this is the authoritative
     * figure after a save.
     *
     * @return array{debit: string, credit: string, difference: string}
     */
    #[Computed]
    public function totals(): array
    {
        $debit = '0';
        $credit = '0';

        foreach ($this->rows as $row) {
            $rate = (string) ($row['exchange_rate'] ?: '1');
            $debit = bcadd($debit, bcmul((string) ($row['debit'] ?: '0'), $rate, 6), 6);
            $credit = bcadd($credit, bcmul((string) ($row['credit'] ?: '0'), $rate, 6), 6);
        }

        return [
            'debit' => $debit,
            'credit' => $credit,
            'difference' => bcsub($debit, $credit, 6),
        ];
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(Journal::APPLICATION_CODE);

        return view('finance::livewire.general-ledger.journals.editor', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'header.journal_date' => ['required', 'date'],
            'header.description' => ['nullable', 'string', 'max:1000'],
            'header.source_reference' => ['nullable', 'string', 'max:255'],
            'rows' => ['array'],
            'rows.*.account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'rows.*.cost_center_id' => ['nullable', 'integer', 'exists:cost_centers,id'],
            'rows.*.currency_id' => ['nullable', 'integer', 'exists:currencies,id'],
            'rows.*.exchange_rate' => ['nullable', 'numeric', 'gt:0'],
            'rows.*.debit' => ['nullable', 'numeric', 'min:0'],
            'rows.*.credit' => ['nullable', 'numeric', 'min:0'],
            'rows.*.description' => ['nullable', 'string', 'max:1000'],
            'rows.*.analysis_code' => ['nullable', 'string', 'max:255'],
            'rows.*.analysis_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'header.journal_date' => __('date'),
            'rows.*.account_id' => __('account'),
            'rows.*.debit' => __('debit'),
            'rows.*.credit' => __('credit'),
        ];
    }

    /**
     * A row nobody has touched is not an error, it is an empty row — it just
     * does not become a line.
     *
     * @param  array<string, mixed>  $row
     */
    protected function isBlankRow(array $row): bool
    {
        return blank($row['account_id'])
            && (float) ($row['debit'] ?: 0) === 0.0
            && (float) ($row['credit'] ?: 0) === 0.0
            && blank($row['description']);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function lineAttributes(array $row, int $lineNumber): array
    {
        $rate = (string) ($row['exchange_rate'] ?: '1');
        $debit = (string) ($row['debit'] ?: '0');
        $credit = (string) ($row['credit'] ?: '0');

        // The account declares which kind of counterparty it needs; the typist
        // supplies the counterparty. Stamping the type only when they actually
        // filled one in keeps the poster's control-account check meaningful.
        $analysisType = null;

        if (filled($row['analysis_code']) || filled($row['analysis_name'])) {
            $analysisType = $this->accountOptions()
                ->firstWhere('id', $row['account_id'])?->required_analysis_type;
        }

        return [
            'line_number' => $lineNumber,
            'account_id' => $row['account_id'] ?: null,
            'cost_center_id' => $row['cost_center_id'] ?: null,
            'description' => $row['description'] ?: null,
            'currency_id' => $row['currency_id'] ?: $this->journal()->ledger->base_currency_id,
            'exchange_rate' => $rate,
            'debit' => $debit,
            'credit' => $credit,
            'base_debit' => bcmul($debit, $rate, 6),
            'base_credit' => bcmul($credit, $rate, 6),
            'analysis_type' => $analysisType,
            'analysis_code' => $row['analysis_code'] ?: null,
            'analysis_name' => $row['analysis_name'] ?: null,
        ];
    }

    protected function trimAmount(mixed $amount): string
    {
        return (float) $amount === 0.0 ? '' : rtrim(rtrim((string) $amount, '0'), '.');
    }
}
