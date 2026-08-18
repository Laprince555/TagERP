<?php

namespace Modules\Finance\Livewire\CashAndBanks;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Livewire\DynamicTable\Table;
use App\Support\DynamicTable\Core\Columns\DateColumn;
use App\Support\DynamicTable\Core\Columns\DateTimeColumn;
use App\Support\DynamicTable\Core\Columns\MoneyColumn;
use App\Support\DynamicTable\Core\Columns\RelationColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\CashAndBanks\BankExpenses\BankExpense;

class BankExpensesTable extends Table
{
    use ChecksApplicationAccess;

    protected string $tableKey = 'finance-cash-and-banks-bank-expenses';

    protected function applicationCode(): string
    {
        return BankExpense::APPLICATION_CODE;
    }

    protected function query(): Builder
    {
        return BankExpense::query()->with('bank');
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('code')->sortable()->searchable()->label('Code'),
            TextColumn::make('number')->sortable()->searchable()->label('Number'),
            RelationColumn::make('bank.bank_name')->label('Bank'),
            DateColumn::make('expense_date')->sortable()->label('Date'),
            TextColumn::make('expense_type')->sortable()->label('Type'),
            MoneyColumn::make('amount')->sortable()->label('Amount'),
            TextColumn::make('status')->sortable()->label('Status'),
            TextColumn::make('reconciliation_status')->sortable()->label('Reconciliation'),
            DateTimeColumn::make('created_at')->sortable()->label('Created'),
        ];
    }

    protected function filters(): array
    {
        return [
            TextFilter::make('number'),
            TextFilter::make('status'),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('expense_date')->descending()];
    }

    protected function createForm(): ?string
    {
        return 'finance.cash-and-banks.bank-expense.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Bank Expense');
    }
}
