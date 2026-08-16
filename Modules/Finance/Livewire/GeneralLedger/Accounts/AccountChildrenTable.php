<?php

namespace Modules\Finance\Livewire\GeneralLedger\Accounts;

use App\Livewire\DynamicTable\Table;
use App\Support\DynamicTable\Core\Columns\BooleanColumn;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\Finance\Models\GeneralLedger\AccountCategory;

/**
 * Embedded-only: the accounts sitting directly under this one — the "Child
 * Accounts" tab on an Account's record view. Never a standalone route, so the
 * parent record view's query() is the access gate.
 */
class AccountChildrenTable extends Table
{
    protected string $tableKey = 'finance-general-ledger-account-children';

    protected ?string $model = Account::class;

    protected function query(): Builder
    {
        return Account::query()->with('category');
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('number')->sortable()->searchable()->label('Number'),
            TextColumn::make('name')->sortable()->searchable()->label('Name'),
            RecordReferenceColumn::make('category')
                ->applicationCode(AccountCategory::APPLICATION_CODE)
                ->relation('category')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Category'),
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
