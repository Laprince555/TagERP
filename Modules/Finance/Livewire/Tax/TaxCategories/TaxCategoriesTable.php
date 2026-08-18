<?php

namespace Modules\Finance\Livewire\Tax\TaxCategories;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Livewire\DynamicTable\Table;
use App\Services\NavigationTreeService;
use App\Support\DynamicTable\Core\Columns\BooleanColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\Tax\TaxCategory;

/**
 * Tax Categories index for the "fin-tax-cat" Application
 * (finance.tax-management.tax-categories).
 */
class TaxCategoriesTable extends Table
{
    protected string $tableKey = 'finance-tax-tax-categories';

    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return TaxCategory::APPLICATION_CODE;
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(TaxCategory::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return TaxCategory::query()->whereKey(-1);
        }

        return TaxCategory::query()->with('country');
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('code')->sortable()->searchable()->label('Code'),
            TextColumn::make('name')->sortable()->searchable()->label('Name'),
            TextColumn::make('country.name')->label('Country'),
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
        return 'finance.tax.tax-category.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Tax Category');
    }
}
