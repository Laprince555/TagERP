<?php

namespace Modules\Finance\Livewire\CashAndBanks;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Livewire\DynamicTable\Table;
use App\Support\DynamicTable\Core\Columns\DateColumn;
use App\Support\DynamicTable\Core\Columns\MoneyColumn;
use App\Support\DynamicTable\Core\Columns\RelationColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\CashAndBanks\Banks\Check;

class ChecksTable extends Table
{
    use ChecksApplicationAccess;

    protected string $tableKey = 'finance-cash-and-banks-checks';

    protected function applicationCode(): string
    {
        return 'fin-cbn-bnk-chk';
    }

    protected function query(): Builder
    {
        return Check::query()->with(['bank', 'checksBook']);
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('code')->sortable()->searchable()->label('Code'),
            TextColumn::make('check_number')->sortable()->searchable()->label('Check Number'),
            DateColumn::make('check_date')->sortable()->label('Date'),
            RelationColumn::make('bank.bank_name')->label('Bank'),
            TextColumn::make('payee_name')->sortable()->searchable()->label('Payee'),
            MoneyColumn::make('amount')->sortable()->label('Amount'),
            TextColumn::make('status')->sortable()->label('Status'),
        ];
    }

    protected function filters(): array
    {
        return [
            TextFilter::make('payee_name'),
            TextFilter::make('status'),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('check_date')->descending()];
    }

    protected function createForm(): ?string
    {
        return 'finance.cash-and-banks.check.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Check');
    }
}
