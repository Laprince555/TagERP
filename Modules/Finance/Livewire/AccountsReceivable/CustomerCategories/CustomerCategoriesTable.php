<?php

namespace Modules\Finance\Livewire\AccountsReceivable\CustomerCategories;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Livewire\DynamicTable\Table;
use App\Services\NavigationTreeService;
use App\Support\DynamicTable\Core\Columns\BooleanColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\AccountsReceivable\CustomerCategory;

/**
 * Customer Categories index for the "fin-ar-cct" Application
 * (finance.accounts-receivable.customer-categories).
 */
class CustomerCategoriesTable extends Table
{
    protected string $tableKey = 'finance-accounts-receivable-customer-categories';

    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return CustomerCategory::APPLICATION_CODE;
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(CustomerCategory::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return CustomerCategory::query()->whereKey(-1);
        }

        return CustomerCategory::query();
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('code')->sortable()->searchable()->label('Code'),
            TextColumn::make('name')->sortable()->searchable()->label('Name'),
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
        return 'finance.accounts-receivable.customer-category.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Customer Category');
    }
}
