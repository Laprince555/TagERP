<?php

namespace Modules\General\Livewire\World\Languages;

use App\Livewire\DynamicTable\Table;
use App\Services\NavigationTreeService;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\General\Models\World\Language;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Real production Languages index for the "gen-wld-lng" Application
 * (general.world.languages), reusing the package Language model/table and
 * the Dynamic Table engine.
 */
class LanguagesTable extends Table
{
    protected string $tableKey = 'general-world-languages';

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
        $application = app(NavigationTreeService::class)->getApplicationByCode('gen-wld-lng');

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
    }

    protected function query(): Builder
    {
        // Re-evaluated on every Livewire render/action, so a disabled
        // Application or revoked permission takes effect immediately —
        // never only at initial mount.
        $application = app(NavigationTreeService::class)->getApplicationByCode('gen-wld-lng');

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Language::query()->whereRaw('1 = 0');
        }

        return Language::query();
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name')->sortable()->searchable()->label('Name'),
            TextColumn::make('name_native')->sortable()->searchable()->label('Native Name'),
            TextColumn::make('code')->sortable()->searchable()->label('Code'),
            TextColumn::make('dir')->sortable()->label('Direction'),
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
