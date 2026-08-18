<?php

namespace Modules\Finance\Livewire\AccountsPayable\Deductions;

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
use Modules\Finance\Models\AccountsPayable\Deduction;
use Modules\Finance\Models\AccountsPayable\DeductionCategory;

/**
 * Deductions index for the "fin-ap-ddc" Application
 * (finance.accounts-payable.deductions).
 */
class DeductionsTable extends Table
{
    protected string $tableKey = 'finance-accounts-payable-deductions';

    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return Deduction::APPLICATION_CODE;
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Deduction::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Deduction::query()->whereKey(-1);
        }

        return Deduction::query()->with('category');
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('code')->sortable()->searchable()->label('Code'),
            TextColumn::make('name')->sortable()->searchable()->label('Name'),
            RecordReferenceColumn::make('category')
                ->applicationCode(DeductionCategory::APPLICATION_CODE)
                ->relation('category')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Category'),
            TextColumn::make('calculation_type')->sortable()->label('Type'),
            TextColumn::make('value')->sortable()->label('Value'),
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
        return 'finance.accounts-payable.deduction.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Deduction');
    }
}
