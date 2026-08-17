<?php

namespace Modules\Finance\Livewire\GeneralLedger\Journals;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Livewire\DynamicTable\Table;
use App\Services\NavigationTreeService;
use App\Support\DynamicTable\Core\Columns\DateColumn;
use App\Support\DynamicTable\Core\Columns\EnumColumn;
use App\Support\DynamicTable\Core\Columns\MoneyColumn;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\DateFilter;
use App\Support\DynamicTable\Core\Filters\EnumFilter;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceAccess;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\GeneralLedger\Journal;
use Modules\Finance\Models\GeneralLedger\JournalStatus;
use Modules\Finance\Models\GeneralLedger\Ledger;

/**
 * Journals index for the "fin-gl-jou" Application
 * (finance.general-ledger.journals).
 */
class JournalsTable extends Table
{
    protected string $tableKey = 'finance-general-ledger-journals';

    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return Journal::APPLICATION_CODE;
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Journal::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Journal::query()->whereKey(-1);
        }

        return Journal::query()->with(['ledger', 'journalBook']);
    }

    protected function columns(): array
    {
        return [
            // The number is the way into the record: an unposted journal has
            // none yet, so the reference provider's code stands in until it
            // does. Sorting and searching still run on the real column.
            RecordReferenceColumn::make('number')
                ->applicationCode(Journal::APPLICATION_CODE)
                ->variant(RecordReferenceVariant::Tag)
                ->sortable()
                ->searchable()
                ->label('Number'),
            DateColumn::make('journal_date')->sortable()->label('Date'),
            RecordReferenceColumn::make('ledger')
                ->applicationCode(Ledger::APPLICATION_CODE)
                ->relation('ledger')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Ledger'),
            TextColumn::make('source_reference')->searchable()->label('Reference')->placeholder('—'),
            MoneyColumn::make('total_debit')->sortable()->label('Total'),
            EnumColumn::make('status')
                ->enum(JournalStatus::class)
                ->sortable()
                ->label('Status')
                ->formatUsing(fn (mixed $value): string => $value instanceof JournalStatus ? $value->label() : (string) $value),
        ];
    }

    protected function filters(): array
    {
        return [
            TextFilter::make('number'),
            DateFilter::make('journal_date'),
            EnumFilter::make('status')->enum(JournalStatus::class),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('journal_date')->descending()];
    }

    protected function createForm(): ?string
    {
        return 'finance.general-ledger.journal.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Journal');
    }
}
