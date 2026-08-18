<?php

namespace Modules\Inventory\Services\StockMovements;

use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\InventorySummary;

class WACValuationService
{
    /**
     * Calculate Weighted Average Cost (WAC) for an item at a location
     * WAC = (Σ Qty × Cost) / Σ Qty
     */
    public function calculateWAC(string $itemId, string $locationId): float
    {
        $movements = InventoryMovement::where('item_id', $itemId)
            ->where('location_id', $locationId)
            ->where('movement_type', 'IN')
            ->get();

        $totalQty = $movements->sum('quantity');
        if ($totalQty == 0) {
            return 0.0;
        }

        $totalCost = $movements->sum(fn ($m) => $m->quantity * $m->cost_per_unit);

        return $totalCost / $totalQty;
    }

    /**
     * Calculate COGS using WAC method
     * COGS = Issued Quantity × WAC per unit
     */
    public function calculateCOGS(
        string $itemId,
        string $locationId,
        int|float $issuedQuantity
    ): float {
        $wac = $this->calculateWAC($itemId, $locationId);

        return $issuedQuantity * $wac;
    }

    /**
     * Get remaining inventory value under WAC
     */
    public function getRemainingInventoryValue(
        string $itemId,
        string $locationId
    ): array {
        $wac = $this->calculateWAC($itemId, $locationId);

        $summary = InventorySummary::where('item_id', $itemId)
            ->where('location_id', $locationId)
            ->first();

        $onHand = $summary?->qty_on_hand ?? 0;
        $totalValue = $onHand * $wac;

        return [
            'quantity_on_hand' => $onHand,
            'wac_per_unit' => $wac,
            'total_value' => $totalValue,
        ];
    }

    /**
     * Get WAC calculation details
     */
    public function getWACDetails(string $itemId, string $locationId): array
    {
        $inMovements = InventoryMovement::where('item_id', $itemId)
            ->where('location_id', $locationId)
            ->where('movement_type', 'IN')
            ->get();

        $totalQty = $inMovements->sum('quantity');
        $totalCost = $inMovements->sum(fn ($m) => $m->quantity * $m->cost_per_unit);
        $wac = $totalQty > 0 ? $totalCost / $totalQty : 0;

        $summary = InventorySummary::where('item_id', $itemId)
            ->where('location_id', $locationId)
            ->first();

        return [
            'total_received_qty' => $totalQty,
            'total_received_cost' => $totalCost,
            'wac_per_unit' => $wac,
            'current_on_hand' => $summary?->qty_on_hand ?? 0,
            'inventory_value' => ($summary?->qty_on_hand ?? 0) * $wac,
            'number_of_receipts' => $inMovements->count(),
        ];
    }

    /**
     * Update WAC periodically (end of period revaluation)
     * This would adjust for any variance between calculated and actual WAC
     */
    public function updatePeriodEndWAC(
        string $itemId,
        string $locationId,
        ?float $actualWAC = null
    ): array {
        $calculatedWAC = $this->calculateWAC($itemId, $locationId);
        $usedWAC = $actualWAC ?? $calculatedWAC;

        $summary = InventorySummary::where('item_id', $itemId)
            ->where('location_id', $locationId)
            ->first();

        if (! $summary) {
            return ['error' => 'Inventory summary not found'];
        }

        $oldValue = $summary->qty_on_hand * $calculatedWAC;
        $newValue = $summary->qty_on_hand * $usedWAC;
        $adjustment = $newValue - $oldValue;

        return [
            'calculated_wac' => $calculatedWAC,
            'actual_wac' => $usedWAC,
            'old_inventory_value' => $oldValue,
            'new_inventory_value' => $newValue,
            'adjustment_amount' => $adjustment,
            'requires_journal_entry' => abs($adjustment) > 0.01,
        ];
    }
}
