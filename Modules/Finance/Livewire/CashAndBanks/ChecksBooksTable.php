<?php

namespace Modules\Finance\Livewire\CashAndBanks;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Livewire\DynamicTable\Table;
use App\Support\DynamicTable\Core\Columns\BooleanColumn;
use App\Support\DynamicTable\Core\Columns\NumberColumn;
use App\Support\DynamicTable\Core\Columns\RelationColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\CashAndBanks\Banks\ChecksBook;

class ChecksBooksTable extends Table
{
    use ChecksApplicationAccess;

    protected string $tableKey = 'finance-cash-and-banks-checks-books';

    protected function applicationCode(): string
    {
        return 'fin-cbn-bnk-cbk';
    }

    protected function query(): Builder
    {
        return ChecksBook::query()->with(['bank', 'bankAccount']);
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('code')->sortable()->searchable()->label('Code'),
            RelationColumn::make('bank.bank_name')->label('Bank'),
            NumberColumn::make('check_series_start')->sortable()->label('Start Number'),
            NumberColumn::make('check_series_end')->sortable()->label('End Number'),
            NumberColumn::make('current_check_number')->sortable()->label('Current Number'),
            TextColumn::make('status')->sortable()->label('Status'),
            BooleanColumn::make('is_active')->sortable()->label('Active'),
        ];
    }

    protected function filters(): array
    {
        return [
            TextFilter::make('status'),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('code')->ascending()];
    }

    protected function createForm(): ?string
    {
        return 'finance.cash-and-banks.checks-book.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Checks Book');
    }
}
