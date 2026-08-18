<?php

namespace Modules\Finance\Livewire\Tax\TaxAdjustments;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Livewire\DynamicTable\Table;
use App\Services\NavigationTreeService;
use App\Support\DynamicTable\Core\Columns\DateColumn;
use App\Support\DynamicTable\Core\Columns\MoneyColumn;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceAccess;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\Tax\Tax;
use Modules\Finance\Models\Tax\TaxAdjustment;

/**
 * Tax Adjustments index for the "fin-tax-adj" Application
 * (finance.tax-management.tax-adjustments).
 */
class TaxAdjustmentsTable extends Table
{
    protected string $tableKey = 'finance-tax-tax-adjustments';

    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return TaxAdjustment::APPLICATION_CODE;
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(TaxAdjustment::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return TaxAdjustment::query()->whereKey(-1);
        }

        return TaxAdjustment::query()->with('tax');
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('code')->sortable()->searchable()->label('Code'),
            RecordReferenceColumn::make('tax')
                ->applicationCode(Tax::APPLICATION_CODE)
                ->relation('tax')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Tax'),
            DateColumn::make('adjustment_date')->sortable()->label('Date'),
            TextColumn::make('reason')->sortable()->label('Reason'),
            MoneyColumn::make('amount')->sortable()->label('Amount'),
            TextColumn::make('status')->sortable()->label('Status'),
        ];
    }

    protected function filters(): array
    {
        return [
            TextFilter::make('code'),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('adjustment_date')->descending()];
    }

    protected function createForm(): ?string
    {
        return 'finance.tax.tax-adjustment.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Tax Adjustment');
    }
}
