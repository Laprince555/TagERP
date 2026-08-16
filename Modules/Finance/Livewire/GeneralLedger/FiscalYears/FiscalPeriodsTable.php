<?php

namespace Modules\Finance\Livewire\GeneralLedger\FiscalYears;

use App\Livewire\DynamicTable\Table;
use App\Support\DynamicTable\Core\Columns\BooleanColumn;
use App\Support\DynamicTable\Core\Columns\DateColumn;
use App\Support\DynamicTable\Core\Columns\NumberColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Sort;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\GeneralLedger\FiscalPeriod;

/**
 * Embedded-only: the periods of one fiscal year — the "Periods" tab on a
 * Fiscal Year's record view. Never a standalone route, so the parent's
 * query() is the access gate.
 *
 * Open/closed state is absent on purpose: it belongs to a ledger, not to the
 * period, and this table has no ledger in hand.
 */
class FiscalPeriodsTable extends Table
{
    protected string $tableKey = 'finance-general-ledger-fiscal-periods';

    protected ?string $model = FiscalPeriod::class;

    protected function query(): Builder
    {
        return FiscalPeriod::query();
    }

    protected function columns(): array
    {
        return [
            NumberColumn::make('sequence')->sortable()->label('#'),
            TextColumn::make('name')->sortable()->searchable()->label('Name'),
            DateColumn::make('start_date')->sortable()->label('Start'),
            DateColumn::make('end_date')->sortable()->label('End'),
            BooleanColumn::make('is_adjustment')->sortable()->label('Adjustment'),
        ];
    }

    protected function filters(): array
    {
        return [];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('sequence')->ascending()];
    }
}
