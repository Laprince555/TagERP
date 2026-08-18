<?php

namespace Modules\Inventory\Livewire\StockMovements;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Livewire\DynamicTable\Table;
use App\Services\NavigationTreeService;
use App\Support\DynamicTable\Core\Columns\DateTimeColumn;
use App\Support\DynamicTable\Core\Columns\NumberColumn;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Columns\RelationColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\DateFilter;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceAccess;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\Inventory\Models\BatchLot;
use Modules\Inventory\Models\InventoryMovement;

/**
 * InventoryMovement index for the "inv-stk-mov" Application
 * (inventory.stock-movements.movements). Append-only audit log: browsable
 * only — see InventoryMovementsIndex/InventoryMovementRecordView for why no
 * createForm() is declared.
 */
class InventoryMovementsTable extends Table
{
    protected string $tableKey = 'inventory-stock-movements-movements';

    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return InventoryMovement::APPLICATION_CODE;
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(InventoryMovement::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return InventoryMovement::query()->whereKey(-1);
        }

        // item/uom are not eager loaded: those relations point at tables
        // with no Eloquent model yet (see item_id/uom_id columns below).
        return InventoryMovement::query()->with(['location.warehouse', 'batchLot', 'createdBy']);
    }

    protected function columns(): array
    {
        return [
            RecordReferenceColumn::make('code')
                ->applicationCode(InventoryMovement::APPLICATION_CODE)
                ->variant(RecordReferenceVariant::Tag)
                ->sortable()
                ->searchable()
                ->label('Code'),
            TextColumn::make('movement_type')->sortable()->searchable()->label('Type'),
            // item_id/uom_id point at tables with no Eloquent model yet (per
            // a prior audit), so they render as raw ids rather than an
            // invented relation/RecordReferenceProvider.
            TextColumn::make('item_id')->label('Item Id'),
            NumberColumn::make('quantity')->decimals(3)->sortable()->label('Quantity'),
            TextColumn::make('uom_id')->label('UOM Id'),
            NumberColumn::make('cost_per_unit')->decimals(4)->sortable()->label('Cost / Unit'),
            // Warehouse is only reachable through location (InventoryMovement
            // has no direct belongsTo to Warehouse), and WarehouseLocation
            // itself is not a registered Application, so this uses
            // RelationColumn rather than RecordReferenceColumn (which only
            // supports a first-level belongsTo to an Application).
            RelationColumn::make('location.warehouse.code')->label('Warehouse'),
            RelationColumn::make('location.location_code')->label('Location'),
            RecordReferenceColumn::make('batchLot')
                ->applicationCode(BatchLot::APPLICATION_CODE)
                ->relation('batchLot')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Batch / Lot'),
            TextColumn::make('reference_doc_type')->label('Reference Type')->placeholder('—'),
            DateTimeColumn::make('created_at')->sortable()->label('Recorded At'),
            RelationColumn::make('createdBy.name')->label('Recorded By'),
        ];
    }

    protected function filters(): array
    {
        return [
            TextFilter::make('movement_type'),
            TextFilter::make('reference_doc_type'),
            DateFilter::make('created_at'),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('created_at')->descending()];
    }

    // No createForm(): movements are created exclusively by
    // InventoryMovementService's locked transactional methods
    // (issueStock()/receiveStock()/transferStock()/allocateStock()), never
    // via a free-form create screen. A generic DynamicForm would let a user
    // insert a row that bypasses those locks and the append-only guard's
    // intent. A dedicated receive/issue/transfer wizard that calls the
    // service is separate, larger work for a later pass.
}
