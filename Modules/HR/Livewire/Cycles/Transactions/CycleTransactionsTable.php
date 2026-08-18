<?php

namespace Modules\HR\Livewire\Cycles\Transactions;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Livewire\DynamicTable\Table;
use App\Services\NavigationTreeService;
use App\Support\DynamicTable\Core\Columns\DateTimeColumn;
use App\Support\DynamicTable\Core\Columns\EnumColumn;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\EnumFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceAccess;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\HR\Models\Cycles\Cycle;
use Modules\HR\Models\Cycles\CycleTransaction;
use Modules\HR\Models\Cycles\CycleTransactionStatus;

/**
 * Index for the "hr-cyc-trx" Application (hr.cycles.transactions). Read-mostly:
 * transactions are started by other modules through CycleTransactionService,
 * never created here.
 */
class CycleTransactionsTable extends Table
{
    protected string $tableKey = 'hr-cycles-transactions';

    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return CycleTransaction::APPLICATION_CODE;
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(CycleTransaction::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return CycleTransaction::query()->whereKey(-1);
        }

        return CycleTransaction::query()->with('cycle', 'subject');
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('code')->sortable()->searchable()->label('Code'),
            RecordReferenceColumn::make('cycle')
                ->applicationCode(Cycle::APPLICATION_CODE)
                ->relation('cycle')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Cycle'),
            TextColumn::make('subject_type')->label('Subject Type'),
            EnumColumn::make('status')->sortable()->labels(CycleTransactionStatus::options())->label('Status'),
            DateTimeColumn::make('started_at')->sortable()->label('Started At'),
            DateTimeColumn::make('completed_at')->sortable()->label('Completed At'),
        ];
    }

    protected function filters(): array
    {
        return [
            EnumFilter::make('status')->options(CycleTransactionStatus::options()),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('started_at')->descending()];
    }

    protected function createForm(): ?string
    {
        return null;
    }
}
