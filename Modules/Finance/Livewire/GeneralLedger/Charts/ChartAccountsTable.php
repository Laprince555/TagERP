<?php

namespace Modules\Finance\Livewire\GeneralLedger\Charts;

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
 * Embedded-only: which accounts (chart_account) this chart includes — the
 * "Accounts" tab on a Chart's record view. Never a standalone route, so the
 * parent Chart's query() is the access gate.
 */
class ChartAccountsTable extends Table
{
    protected string $tableKey = 'finance-general-ledger-chart-accounts';

    protected ?string $model = Account::class;

    protected function query(): Builder
    {
        return Account::query()->with(['parent', 'category']);
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('number')->sortable()->searchable()->label('Number'),
            TextColumn::make('name')->sortable()->searchable()->label('Name'),
            RecordReferenceColumn::make('parent')
                ->applicationCode(Account::APPLICATION_CODE)
                ->relation('parent')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Parent'),
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
