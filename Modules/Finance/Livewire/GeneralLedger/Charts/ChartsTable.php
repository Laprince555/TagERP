<?php

namespace Modules\Finance\Livewire\GeneralLedger\Charts;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Livewire\DynamicTable\Table;
use App\Services\NavigationTreeService;
use App\Support\DynamicTable\Core\Columns\BooleanColumn;
use App\Support\DynamicTable\Core\Columns\NumberColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\GeneralLedger\Chart;

/**
 * Charts of Accounts index for the "fin-gl-coa" Application
 * (finance.general-ledger.charts).
 */
class ChartsTable extends Table
{
    protected string $tableKey = 'finance-general-ledger-charts';

    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return Chart::APPLICATION_CODE;
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Chart::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Chart::query()->whereKey(-1);
        }

        return Chart::query();
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name')->sortable()->searchable()->label('Name'),
            NumberColumn::make('levels_count')->sortable()->label('Levels'),
            BooleanColumn::make('is_active')->sortable()->label('Active'),
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
        return 'finance.general-ledger.chart.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Chart of Accounts');
    }
}
