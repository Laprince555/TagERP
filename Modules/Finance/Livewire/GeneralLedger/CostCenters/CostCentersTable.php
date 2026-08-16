<?php

namespace Modules\Finance\Livewire\GeneralLedger\CostCenters;

use App\Livewire\DynamicTable\Table;
use App\Services\NavigationTreeService;
use App\Support\DynamicTable\Core\Columns\BooleanColumn;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceAccess;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\GeneralLedger\CostCenter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Cost Centres index for the "fin-gl-cct" Application
 * (finance.general-ledger.cost-centers).
 */
class CostCentersTable extends Table
{
    protected string $tableKey = 'finance-general-ledger-cost-centers';

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
        $application = app(NavigationTreeService::class)->getApplicationByCode(CostCenter::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(CostCenter::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return CostCenter::query()->whereRaw('1 = 0');
        }

        return CostCenter::query()->with('parent');
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('number')->sortable()->searchable()->label('Number'),
            TextColumn::make('name')->sortable()->searchable()->label('Name'),
            RecordReferenceColumn::make('parent')
                ->applicationCode(CostCenter::APPLICATION_CODE)
                ->relation('parent')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Parent'),
            BooleanColumn::make('accepts_transactions')->sortable()->label('Accepts Transactions'),
            BooleanColumn::make('is_active')->sortable()->label('Active'),
        ];
    }

    protected function filters(): array
    {
        return [
            TextFilter::make('number'),
            TextFilter::make('name'),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('number')->ascending()];
    }

    protected function createForm(): ?string
    {
        return 'finance.general-ledger.cost-center.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Cost Centre');
    }
}
