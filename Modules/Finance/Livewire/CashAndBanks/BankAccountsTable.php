<?php

namespace Modules\Finance\Livewire\CashAndBanks;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Livewire\DynamicTable\Table;
use App\Support\DynamicTable\Core\Columns\BooleanColumn;
use App\Support\DynamicTable\Core\Columns\MoneyColumn;
use App\Support\DynamicTable\Core\Columns\RelationColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\CashAndBanks\Banks\BankAccount;

class BankAccountsTable extends Table
{
    use ChecksApplicationAccess;

    protected string $tableKey = 'finance-cash-and-banks-bank-accounts';

    protected function applicationCode(): string
    {
        return 'fin-cbn-bnk-bacc';
    }

    protected function query(): Builder
    {
        return BankAccount::query()->with(['bank', 'currency']);
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('code')->sortable()->searchable()->label('Code'),
            TextColumn::make('account_number')->sortable()->searchable()->label('Account Number'),
            TextColumn::make('account_name')->sortable()->searchable()->label('Account Name'),
            TextColumn::make('account_type')->sortable()->label('Type'),
            RelationColumn::make('currency.code')->label('Currency'),
            MoneyColumn::make('balance')->sortable()->label('Balance'),
            BooleanColumn::make('is_active')->sortable()->label('Active'),
        ];
    }

    protected function filters(): array
    {
        return [
            TextFilter::make('account_number'),
            TextFilter::make('account_name'),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('account_number')->ascending()];
    }

    protected function createForm(): ?string
    {
        return 'finance.cash-and-banks.bank-account.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Bank Account');
    }
}
