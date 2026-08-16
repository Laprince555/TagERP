<?php

namespace Modules\Finance\Livewire\GeneralLedger\Charts;

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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Charts of Accounts index for the "fin-gl-coa" Application
 * (finance.general-ledger.charts).
 */
class ChartsTable extends Table
{
    protected string $tableKey = 'finance-general-ledger-charts';

    public function boot(): void
    {
        $this->checkAccess();
    }

    public function hydrate(): void
    {
        $this->checkAccess();
    }

    protected function checkAccess(): void
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Chart::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Chart::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Chart::query()->whereRaw('1 = 0');
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
