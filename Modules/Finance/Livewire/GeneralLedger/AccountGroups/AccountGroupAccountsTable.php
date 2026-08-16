<?php

namespace Modules\Finance\Livewire\GeneralLedger\AccountGroups;

use App\Livewire\DynamicTable\Table;
use App\Support\DynamicTable\Core\Columns\BooleanColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Sort;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\GeneralLedger\Account;

/**
 * Embedded-only: the accounts this group contains — the "Accounts" tab on an
 * Account Group's record view.
 *
 * Deliberately not narrowed by AccountAccessResolver: this is the screen where
 * access is granted, and somebody who could not see an account could never
 * grant it to anybody else either.
 */
class AccountGroupAccountsTable extends Table
{
    protected string $tableKey = 'finance-general-ledger-account-group-accounts';

    protected ?string $model = Account::class;

    protected function query(): Builder
    {
        return Account::query();
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('number')->sortable()->searchable()->label('Number'),
            TextColumn::make('name')->sortable()->searchable()->label('Name'),
            BooleanColumn::make('is_active')->sortable()->label('Active'),
        ];
    }

    protected function filters(): array
    {
        return [];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('number')->ascending()];
    }
}
