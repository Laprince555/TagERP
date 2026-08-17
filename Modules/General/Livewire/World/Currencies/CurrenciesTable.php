<?php

namespace Modules\General\Livewire\World\Currencies;

use App\Livewire\DynamicTable\Table;
use App\Services\NavigationTreeService;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceAccess;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\General\Models\World\Currency;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Real production Currencies index for the "gen-wld-cur" Application
 * (general.world.currencies), reusing the package Currency model/table and
 * the Dynamic Table engine.
 */
class CurrenciesTable extends Table
{
    protected string $tableKey = 'general-world-currencies';

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
        $application = app(NavigationTreeService::class)->getApplicationByCode('gen-wld-cur');

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
    }

    protected function query(): Builder
    {
        // Re-evaluated on every Livewire render/action, so a disabled
        // Application or revoked permission takes effect immediately —
        // never only at initial mount.
        $application = app(NavigationTreeService::class)->getApplicationByCode('gen-wld-cur');

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Currency::query()->whereRaw('1 = 0');
        }

        return Currency::query()->with('country');
    }

    protected function columns(): array
    {
        return [
            RecordReferenceColumn::make('name')
                ->applicationCode('gen-wld-cur')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Name')
                ->sortable(),
            TextColumn::make('code')->sortable()->searchable()->label('Code'),
            TextColumn::make('symbol')->sortable()->label('Symbol'),
            RecordReferenceColumn::make('country')
                ->applicationCode('gen-wld-ctr')
                ->relation('country')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Country'),
        ];
    }

    protected function filters(): array
    {
        return [
            TextFilter::make('name'),
            TextFilter::make('code'),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('name')->ascending()];
    }
}
