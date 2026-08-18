<?php

namespace Modules\Inventory\Services\StockMovements;

use Modules\Inventory\Models\BatchLot;
use Modules\Inventory\Models\WarehouseLocation;

class FIFOValuationService
{
    /**
     * Calculate COGS using FIFO method (oldest batches consumed first)
     *
     * @throws \Exception if not enough inventory
     */
    public function calculateCOGS(
        string $itemId,
        string $locationId,
        int|float $issuedQuantity
    ): float {
        $warehouse = WarehouseLocation::find($locationId)->warehouse;

        $batches = BatchLot::where('item_id', $itemId)
            ->where('warehouse_id', $warehouse->id)
            ->where('qty_available', '>', 0)
            ->orderBy('created_at', 'ASC') // Oldest first (FIFO)
            ->get();

        $totalCost = 0.0;
        $remainingQty = $issuedQuantity;

        foreach ($batches as $batch) {
            if ($remainingQty <= 0) {
                break;
            }

            $consumedQty = min($batch->qty_available, $remainingQty);
            $totalCost += $consumedQty * $batch->cost_per_unit;
            $remainingQty -= $consumedQty;
        }

        if ($remainingQty > 0) {
            throw new \Exception(
                "Not enough inventory to fulfill FIFO request. Shortage: {$remainingQty}"
            );
        }

        return $totalCost;
    }

    /**
     * Get remaining inventory value under FIFO
     */
    public function getRemainingInventoryValue(
        string $itemId,
        string $locationId
    ): array {
        $warehouse = WarehouseLocation::find($locationId)->warehouse;

        $batches = BatchLot::where('item_id', $itemId)
            ->where('warehouse_id', $warehouse->id)
            ->where('qty_available', '>', 0)
            ->orderBy('created_at', 'ASC')
            ->get();

        $totalValue = 0.0;
        $details = [];

        foreach ($batches as $batch) {
            $batchValue = $batch->qty_available * $batch->cost_per_unit;
            $totalValue += $batchValue;

            $details[] = [
                'batch_number' => $batch->batch_number,
                'quantity' => $batch->qty_available,
                'cost_per_unit' => $batch->cost_per_unit,
                'total_value' => $batchValue,
                'expiry_date' => $batch->expiry_date?->format('Y-m-d'),
                'days_until_expiry' => $batch->days_until_expiry,
            ];
        }

        return [
            'total_value' => $totalValue,
            'batches' => $details,
            'batch_count' => count($details),
        ];
    }

    /**
     * Get detailed consumption breakdown by batch
     */
    public function getConsumptionDetails(
        string $itemId,
        string $locationId,
        int|float $issuedQuantity
    ): array {
        $warehouse = WarehouseLocation::find($locationId)->warehouse;

        $batches = BatchLot::where('item_id', $itemId)
            ->where('warehouse_id', $warehouse->id)
            ->where('qty_available', '>', 0)
            ->orderBy('created_at', 'ASC')
            ->get();

        $consumed = [];
        $totalCost = 0.0;
        $remainingQty = $issuedQuantity;

        foreach ($batches as $batch) {
            if ($remainingQty <= 0) {
                break;
            }

            $consumedQty = min($batch->qty_available, $remainingQty);
            $cost = $consumedQty * $batch->cost_per_unit;

            $consumed[] = [
                'batch_number' => $batch->batch_number,
                'consumed_qty' => $consumedQty,
                'cost_per_unit' => $batch->cost_per_unit,
                'cost' => $cost,
                'created_at' => $batch->created_at->format('Y-m-d'),
            ];

            $totalCost += $cost;
            $remainingQty -= $consumedQty;
        }

        return [
            'total_cogs' => $totalCost,
            'consumed_batches' => collect($consumed),
            'shortage' => max(0, $remainingQty),
            'batches_used' => count($consumed),
        ];
    }

    /**
     * Compare FIFO with WAC for decision making
     */
    public function compareFIFOWithWAC(
        string $itemId,
        string $locationId,
        int|float $issuedQuantity
    ): array {
        $wacService = app(WACValuationService::class);

        $fifoCOGS = $this->calculateCOGS($itemId, $locationId, $issuedQuantity);
        $wacCOGS = $wacService->calculateCOGS($itemId, $locationId, $issuedQuantity);

        $difference = $fifoCOGS - $wacCOGS;
        $percentageDifference = ($difference / $wacCOGS) * 100;

        return [
            'fifo_cogs' => $fifoCOGS,
            'wac_cogs' => $wacCOGS,
            'difference' => $difference,
            'percentage_difference' => round($percentageDifference, 2),
            'recommendation' => $difference > 0
                ? 'FIFO is more expensive (cost inflation)'
                : 'FIFO is cheaper (cost deflation)',
        ];
    }
}
