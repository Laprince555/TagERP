<?php

namespace Modules\Finance\Livewire\GeneralLedger\Ledgers;

use App\Livewire\DynamicTable\Table;
use App\Services\NavigationTreeService;
use App\Support\DynamicTable\Core\Columns\BooleanColumn;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Columns\RelationColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\BooleanFilter;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceAccess;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\GeneralLedger\Chart;
use Modules\Finance\Models\GeneralLedger\Ledger;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Ledgers index for the "fin-gl-led" Application
 * (finance.general-ledger.ledgers).
 */
class LedgersTable extends Table
{
    protected string $tableKey = 'finance-general-ledger-ledgers';

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
        $application = app(NavigationTreeService::class)->getApplicationByCode(Ledger::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Ledger::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Ledger::query()->whereRaw('1 = 0');
        }

        return Ledger::query()->with(['entity', 'chart', 'baseCurrency']);
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name')->sortable()->searchable()->label('Name'),
            RecordReferenceColumn::make('entity')
                ->applicationCode('hr-org-ent')
                ->relation('entity')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Entity'),
            RecordReferenceColumn::make('chart')
                ->applicationCode(Chart::APPLICATION_CODE)
                ->relation('chart')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Chart'),
            RelationColumn::make('baseCurrency.code')->label('Currency'),
            BooleanColumn::make('is_primary')->sortable()->label('Primary'),
            BooleanColumn::make('is_active')->sortable()->label('Active'),
        ];
    }

    protected function filters(): array
    {
        return [
            TextFilter::make('name'),
            BooleanFilter::make('is_primary'),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('name')->ascending()];
    }

    protected function createForm(): ?string
    {
        return 'finance.general-ledger.ledger.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Ledger');
    }
}
