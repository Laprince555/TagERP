<?php

namespace Modules\Inventory\Livewire\StockMovements;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Livewire\DynamicTable\Table;
use App\Services\NavigationTreeService;
use App\Support\DynamicTable\Core\Columns\DateColumn;
use App\Support\DynamicTable\Core\Columns\NumberColumn;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\DateFilter;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceAccess;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\Inventory\Models\BatchLot;
use Modules\Inventory\Models\Warehouse;

/**
 * Batch Lots index for the "inv-stk-bat" Application
 * (inventory.stock-movements.batch-lots).
 */
class BatchLotsTable extends Table
{
    protected string $tableKey = 'inventory-stock-movements-batch-lots';

    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return BatchLot::APPLICATION_CODE;
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(BatchLot::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return BatchLot::query()->whereKey(-1);
        }

        return BatchLot::query()->with('warehouse');
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('batch_number')->sortable()->searchable()->label('Batch Number'),
            // item_id has no Eloquent model yet — plain id display until one exists.
            NumberColumn::make('item_id')->sortable()->label('Item ID'),
            RecordReferenceColumn::make('warehouse')
                ->applicationCode(Warehouse::APPLICATION_CODE)
                ->relation('warehouse')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Warehouse'),
            DateColumn::make('expiry_date')->sortable()->label('Expiry Date'),
            NumberColumn::make('cost_per_unit')->decimals(4)->sortable()->label('Cost Per Unit'),
            NumberColumn::make('qty_available')->decimals(3)->sortable()->label('Available'),
        ];
    }

    protected function filters(): array
    {
        return [
            TextFilter::make('batch_number'),
            DateFilter::make('expiry_date'),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('expiry_date')->ascending()];
    }

    protected function createForm(): ?string
    {
        return 'inventory.stock-movements.batch-lot.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Batch Lot');
    }
}
