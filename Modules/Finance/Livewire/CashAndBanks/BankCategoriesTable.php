<?php

namespace Modules\Finance\Livewire\CashAndBanks;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Livewire\DynamicTable\Table;
use App\Support\DynamicTable\Core\Columns\BooleanColumn;
use App\Support\DynamicTable\Core\Columns\DateTimeColumn;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\CashAndBanks\Categories\BankCategory;

class BankCategoriesTable extends Table
{
    use ChecksApplicationAccess;

    protected string $tableKey = 'finance-cash-and-banks-bank-categories';

    protected function applicationCode(): string
    {
        return BankCategory::APPLICATION_CODE;
    }

    protected function query(): Builder
    {
        return BankCategory::query()->with('parent');
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('code')->sortable()->searchable()->label('Code'),
            TextColumn::make('name')->sortable()->searchable()->label('Name'),
            RecordReferenceColumn::make('parent')
                ->applicationCode(BankCategory::APPLICATION_CODE)
                ->relation('parent')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Parent Category'),
            BooleanColumn::make('is_active')->sortable()->label('Active'),
            DateTimeColumn::make('created_at')->sortable()->label('Created'),
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
        return 'finance.cash-and-banks.bank-category.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Bank Category');
    }
}
