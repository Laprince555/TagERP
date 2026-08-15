<?php

namespace Modules\General\Livewire\World\Countries;

use App\Livewire\DynamicTable\Table;
use App\Services\NavigationTreeService;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceAccess;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\General\Models\World\City;

/**
 * Ordinary, reusable Dynamic Table for Cities. Works standalone (the
 * "gen-wld-cty" Application, unconstrained — every City) and embedded
 * inside CountryRecordView's/StateRecordView's "Cities" Other Data tab,
 * where the base Table class layers the parent's relation constraint on
 * top via EmbeddedTableContext (see Table::resolvedQuery()). The access
 * gate only guards the standalone Application route — an embedded instance
 * is already gated by its parent record's own query().
 */
class CitiesTable extends Table
{
    protected string $tableKey = 'general.world.cities';

    protected ?string $model = City::class;

    /**
     * Standalone-only gate: an embedded instance is already gated by its
     * parent record's own query() (CountryRecordView, StateRecordView), and
     * $embedRecordViewKey is only populated by mount() — not yet set when a
     * boot()/hydrate() hook would run on the very first request — so the
     * check has to live here, in query(), which always runs after mount().
     */
    protected function query(): Builder
    {
        if ($this->embedRecordViewKey === '') {
            $application = app(NavigationTreeService::class)->getApplicationByCode('gen-wld-cty');

            if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
                return City::query()->whereRaw('1 = 0');
            }
        }

        return City::query();
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name')->sortable()->searchable()->label('Name'),
            RecordReferenceColumn::make('country')
                ->applicationCode('gen-wld-ctr')
                ->relation('country')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Country'),
            RecordReferenceColumn::make('state')
                ->applicationCode('gen-wld-sta')
                ->relation('state')
                ->variant(RecordReferenceVariant::Tag)
                ->label('State'),
            TextColumn::make('country_code')->sortable()->searchable()->label('Country Code'),
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
