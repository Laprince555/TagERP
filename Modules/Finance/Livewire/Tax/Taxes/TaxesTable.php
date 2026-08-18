<?php

namespace Modules\Finance\Livewire\Tax\Taxes;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Livewire\DynamicTable\Table;
use App\Services\NavigationTreeService;
use App\Support\DynamicTable\Core\Columns\BooleanColumn;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceAccess;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\Tax\Tax;
use Modules\Finance\Models\Tax\TaxCategory;

/**
 * Taxes index for the "fin-tax-tax" Application (finance.tax-management.taxes).
 */
class TaxesTable extends Table
{
    protected string $tableKey = 'finance-tax-taxes';

    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return Tax::APPLICATION_CODE;
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Tax::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Tax::query()->whereKey(-1);
        }

        return Tax::query()->with('category');
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('code')->sortable()->searchable()->label('Code'),
            TextColumn::make('name')->sortable()->searchable()->label('Name'),
            RecordReferenceColumn::make('category')
                ->applicationCode(TaxCategory::APPLICATION_CODE)
                ->relation('category')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Category'),
            TextColumn::make('direction')->sortable()->label('Direction'),
            TextColumn::make('rate')->sortable()->label('Rate (%)'),
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
        return [Sort::make('name')->ascending()];
    }

    protected function createForm(): ?string
    {
        return 'finance.tax.tax.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Tax');
    }
}
