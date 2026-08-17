<?php

namespace Modules\General\Livewire\World\Timezones;

use App\Livewire\DynamicTable\Table;
use App\Services\NavigationTreeService;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceAccess;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\General\Models\World\Timezone;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Real production Time Zones index for the "gen-wld-tzn" Application
 * (general.world.timezones), reusing the package Timezone model/table and
 * the Dynamic Table engine.
 */
class TimezonesTable extends Table
{
    protected string $tableKey = 'general-world-timezones';

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
        $application = app(NavigationTreeService::class)->getApplicationByCode('gen-wld-tzn');

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
    }

    protected function query(): Builder
    {
        // Re-evaluated on every Livewire render/action, so a disabled
        // Application or revoked permission takes effect immediately —
        // never only at initial mount.
        $application = app(NavigationTreeService::class)->getApplicationByCode('gen-wld-tzn');

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Timezone::query()->whereRaw('1 = 0');
        }

        return Timezone::query()->with('country');
    }

    protected function columns(): array
    {
        return [
            RecordReferenceColumn::make('name')
                ->sortable()
                ->searchable()
                ->applicationCode('gen-wld-tzn')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Name'),
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
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('name')->ascending()];
    }
}
