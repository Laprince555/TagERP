<?php

namespace Modules\Inventory\Services\StockMovements;

use Illuminate\Support\Collection;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\InventorySummary;

class StockQueryService
{
    /**
     * Get available stock for item at location
     */
    public function getAvailableStock(string $itemId, string $locationId): float
    {
        $summary = InventorySummary::where('item_id', $itemId)
            ->where('location_id', $locationId)
            ->first();

        if (! $summary) {
            return 0;
        }

        return max(0, $summary->qty_on_hand - $summary->qty_allocated);
    }

    /**
     * Get on-hand stock
     */
    public function getOnHandStock(string $itemId, string $locationId): float
    {
        return InventorySummary::where('item_id', $itemId)
            ->where('location_id', $locationId)
            ->value('qty_on_hand') ?? 0;
    }

    /**
     * Get allocated stock
     */
    public function getAllocatedStock(string $itemId, string $locationId): float
    {
        return InventorySummary::where('item_id', $itemId)
            ->where('location_id', $locationId)
            ->value('qty_allocated') ?? 0;
    }

    /**
     * Get stock across all locations for an item
     */
    public function getStockAcrossLocations(string $itemId): Collection
    {
        return InventorySummary::where('item_id', $itemId)
            ->with(['location.warehouse', 'location.warehouse.branch'])
            ->where('qty_on_hand', '>', 0)
            ->orderBy('qty_on_hand', 'DESC')
            ->get();
    }

    /**
     * Get total available stock across all locations
     */
    public function getTotalAvailableStock(string $itemId): float
    {
        return InventorySummary::where('item_id', $itemId)
            ->selectRaw('SUM(qty_on_hand - qty_allocated) as total')
            ->value('total') ?? 0;
    }

    /**
     * Find item in closest warehouse
     */
    public function findClosestAvailableItem(
        string $itemId,
        string $originWarehouseId,
        int $requiredQty
    ): ?InventorySummary {
        return InventorySummary::where('item_id', $itemId)
            ->selectRaw('inventory_summary.*, (qty_on_hand - qty_allocated) as available')
            ->whereRaw('(qty_on_hand - qty_allocated) >= ?', [$requiredQty])
            ->whereHas('location', function ($q) use ($originWarehouseId) {
                $q->where('warehouse_id', $originWarehouseId)
                    ->orWhere('warehouse_id', '<>', $originWarehouseId);
            })
            ->with('location.warehouse')
            ->orderBy('available', 'DESC')
            ->first();
    }

    /**
     * Get low stock items for warehouse
     */
    public function getLowStockItems(string $warehouseId, $threshold = null): Collection
    {
        return InventorySummary::whereHas('location', function ($q) use ($warehouseId) {
            $q->where('warehouse_id', $warehouseId);
        })
            ->whereRaw(
                $threshold
                    ? '(qty_on_hand - qty_allocated) <= ?'
                    : 'qty_on_hand <= items.reorder_point',
                $threshold ? [$threshold] : []
            )
            ->with(['item', 'location'])
            ->orderBy('qty_on_hand', 'ASC')
            ->get();
    }

    /**
     * Get movement history for item at location
     */
    public function getMovementHistory(
        string $itemId,
        string $locationId,
        int $limit = 100
    ): Collection {
        return InventoryMovement::where('item_id', $itemId)
            ->where('location_id', $locationId)
            ->with(['createdBy', 'batchLot'])
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Get movements for a reference document
     */
    public function getMovementsForReference(string $refType, string $refId): Collection
    {
        return InventoryMovement::where('reference_doc_type', $refType)
            ->where('reference_doc_id', $refId)
            ->with(['item', 'location', 'createdBy'])
            ->get();
    }

    /**
     * Calculate stock velocity (turnover rate)
     */
    public function getStockVelocity(string $itemId, string $locationId, int $days = 30): float
    {
        $outgoingQty = InventoryMovement::where('item_id', $itemId)
            ->where('location_id', $locationId)
            ->whereIn('movement_type', ['OUT'])
            ->where('created_at', '>=', now()->subDays($days))
            ->sum('quantity');

        return $outgoingQty / $days;
    }
}
