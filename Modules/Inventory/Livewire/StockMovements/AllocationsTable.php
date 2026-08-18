<?php

namespace Modules\Inventory\Livewire\StockMovements;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Livewire\DynamicTable\Table;
use App\Services\NavigationTreeService;
use App\Support\DynamicTable\Core\Columns\NumberColumn;
use App\Support\DynamicTable\Core\Columns\RelationColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\Inventory\Models\Allocation;

/**
 * Allocations index for the "inv-stk-alc" Application
 * (inventory.stock-movements.allocations).
 */
class AllocationsTable extends Table
{
    protected string $tableKey = 'inventory-stock-movements-allocations';

    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return Allocation::APPLICATION_CODE;
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Allocation::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Allocation::query()->whereKey(-1);
        }

        return Allocation::query()->with('location');
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('code')->sortable()->searchable()->label('Code'),
            // item_id has no Eloquent model yet — plain id display until one exists.
            NumberColumn::make('item_id')->sortable()->label('Item ID'),
            // WarehouseLocation has no APPLICATION_CODE/RecordReferenceProvider
            // registered as an Application yet — dotted relation column, not RecordReferenceColumn.
            RelationColumn::make('location.bin')->label('Location Bin'),
            NumberColumn::make('allocated_qty')->decimals(3)->sortable()->label('Allocated'),
            TextColumn::make('status')->sortable()->searchable()->label('Status'),
            TextColumn::make('reference_order_type')->sortable()->searchable()->label('Reference Type'),
        ];
    }

    protected function filters(): array
    {
        return [
            TextFilter::make('code'),
            TextFilter::make('status'),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('code')->descending()];
    }

    protected function createForm(): ?string
    {
        return 'inventory.stock-movements.allocation.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Allocation');
    }
}
