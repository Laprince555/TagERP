<?php

namespace Modules\General\Livewire\World\Countries;

use App\Livewire\DynamicTable\Table;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use Nnjeim\World\Models\City;

/**
 * Ordinary, reusable Dynamic Table for Cities. Works standalone
 * (unconstrained — every City) and embedded inside CountryRecordView's
 * "Cities" Other Data tab, where the base Table class layers the parent's
 * relation constraint on top via EmbeddedTableContext (see
 * Table::resolvedQuery()). No parent-specific query() override, so this
 * class never knows whether it is being embedded or by whom.
 */
class CitiesTable extends Table
{
    protected string $tableKey = 'general.world.cities';

    protected ?string $model = City::class;

    protected function columns(): array
    {
        return [
            TextColumn::make('name')->sortable()->searchable()->label('Name'),
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
