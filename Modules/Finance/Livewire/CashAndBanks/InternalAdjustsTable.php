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
use Modules\Finance\Models\CashAndBanks\InternalAdjust\InternalAdjust;

class InternalAdjustsTable extends Table
{
    use ChecksApplicationAccess;

    protected string $tableKey = 'finance-cash-and-banks-internal-adjusts';

    protected function applicationCode(): string
    {
        return InternalAdjust::APPLICATION_CODE;
    }

    protected function query(): Builder
    {
        return InternalAdjust::query()->with(['fromBank', 'fromSafe', 'toBank', 'toSafe', 'currency']);
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('code')->sortable()->searchable()->label('Code'),
            TextColumn::make('number')->sortable()->searchable()->label('Number'),
            DateColumn::make('adjustment_date')->sortable()->label('Date'),
            MoneyColumn::make('amount')->sortable()->label('Amount'),
            RelationColumn::make('currency.code')->label('Currency'),
            TextColumn::make('status')->sortable()->label('Status'),
            DateTimeColumn::make('posted_at')->sortable()->label('Posted'),
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
        return [Sort::make('adjustment_date')->descending()];
    }

    protected function createForm(): ?string
    {
        return 'finance.cash-and-banks.internal-adjust.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Internal Adjustment');
    }
}
