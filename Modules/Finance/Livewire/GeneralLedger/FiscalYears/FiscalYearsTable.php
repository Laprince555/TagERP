<?php

namespace Modules\Finance\Livewire\GeneralLedger\FiscalYears;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Livewire\DynamicTable\Table;
use App\Services\NavigationTreeService;
use App\Support\DynamicTable\Core\Columns\BooleanColumn;
use App\Support\DynamicTable\Core\Columns\DateColumn;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceAccess;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\GeneralLedger\FiscalYear;

/**
 * Fiscal Years index for the "fin-gl-fyr" Application
 * (finance.general-ledger.fiscal-years).
 */
class FiscalYearsTable extends Table
{
    protected string $tableKey = 'finance-general-ledger-fiscal-years';

    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return FiscalYear::APPLICATION_CODE;
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(FiscalYear::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return FiscalYear::query()->whereKey(-1);
        }

        return FiscalYear::query()->with('entity');
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name')->sortable()->searchable()->label('Name'),
            RecordReferenceColumn::make('entity')
                ->applicationCode('hr-org-ent')
                ->relation('entity')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Entity'),
            DateColumn::make('start_date')->sortable()->label('Start'),
            DateColumn::make('end_date')->sortable()->label('End'),
            BooleanColumn::make('is_active')->sortable()->label('Active'),
        ];
    }

    protected function filters(): array
    {
        return [
            TextFilter::make('name'),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('start_date')->descending()];
    }

    protected function createForm(): ?string
    {
        return 'finance.general-ledger.fiscal-year.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Fiscal Year');
    }
}
