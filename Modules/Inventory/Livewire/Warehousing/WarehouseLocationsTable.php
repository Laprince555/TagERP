<?php

namespace Modules\Inventory\Livewire\Warehousing;

use App\Livewire\DynamicTable\Table;
use App\Support\DynamicTable\Core\Columns\NumberColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Sort;
use Illuminate\Database\Eloquent\Builder;
use Modules\Inventory\Models\WarehouseLocation;

/**
 * Embedded-only: the locations of one Warehouse — the "Locations" tab on a
 * Warehouse's record view. Never a standalone route, so the parent's query()
 * is the access gate, same shape as ApInvoiceLinesTable.
 */
class WarehouseLocationsTable extends Table
{
    protected string $tableKey = 'inventory-warehousing-warehouse-locations';

    protected ?string $model = WarehouseLocation::class;

    protected function query(): Builder
    {
        return WarehouseLocation::query();
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('code')->sortable()->label('Code'),
            NumberColumn::make('level')->sortable()->label('Level'),
            TextColumn::make('aisle')->label('Aisle'),
            TextColumn::make('shelf')->label('Shelf'),
            TextColumn::make('bin')->label('Bin'),
            NumberColumn::make('capacity_units')->sortable()->label('Capacity (units)'),
        ];
    }

    protected function filters(): array
    {
        return [];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('level')->ascending()];
    }
}
