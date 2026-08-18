<?php

namespace Modules\Inventory\Services\StockMovements;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Exceptions\InsufficientStockException;
use Modules\Inventory\Models\Allocation;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\InventorySummary;
use Modules\Inventory\Models\Item;

class InventoryMovementService
{
    /**
     * Issue stock (OUT movement) with pessimistic locking
     * 🔒 CRITICAL: Uses lockForUpdate() to prevent race conditions
     *
     * @throws InsufficientStockException
     */
    public function issueStock(
        string $itemId,
        string $locationId,
        int|float $quantity,
        string $refDocType,
        string $refDocId,
        ?string $notes = null
    ): InventoryMovement {
        return DB::transaction(function () use (
            $itemId, $locationId, $quantity, $refDocType, $refDocId, $notes
        ) {
            // 1. Lock the summary row (PESSIMISTIC LOCK)
            $summary = InventorySummary::where('item_id', $itemId)
                ->where('location_id', $locationId)
                ->lockForUpdate()
                ->first();

            if (! $summary) {
                throw new \Exception("Inventory summary not found for item {$itemId} at location {$locationId}");
            }

            // 2. Validate available stock (OnHand - Allocated)
            $available = $summary->qty_on_hand - $summary->qty_allocated;
            if ($available < $quantity) {
                throw new InsufficientStockException(
                    "Insufficient stock. Available: {$available}, Requested: {$quantity}"
                );
            }

            // 3. Create immutable movement record
            $movement = InventoryMovement::create([
                'item_id' => $itemId,
                'location_id' => $locationId,
                'movement_type' => 'OUT',
                'quantity' => $quantity,
                'uom_id' => Item::find($itemId)->base_uom_id,
                'reference_doc_type' => $refDocType,
                'reference_doc_id' => $refDocId,
                'created_by' => Auth::id(),
                'notes' => $notes,
            ]);

            // 4. Update summary (within same transaction)
            $summary->update([
                'qty_on_hand' => $summary->qty_on_hand - $quantity,
                'cached_at' => now(),
            ]);

            return $movement;
        }, attempts: 3);
    }

    /**
     * Receive stock (IN movement)
     */
    public function receiveStock(
        string $itemId,
        string $locationId,
        int|float $quantity,
        string $refDocType,
        string $refDocId,
        ?float $costPerUnit = null,
        ?string $notes = null
    ): InventoryMovement {
        return DB::transaction(function () use (
            $itemId, $locationId, $quantity, $refDocType, $refDocId, $costPerUnit, $notes
        ) {
            // Create movement
            $movement = InventoryMovement::create([
                'item_id' => $itemId,
                'location_id' => $locationId,
                'movement_type' => 'IN',
                'quantity' => $quantity,
                'uom_id' => Item::find($itemId)->base_uom_id,
                'cost_per_unit' => $costPerUnit,
                'reference_doc_type' => $refDocType,
                'reference_doc_id' => $refDocId,
                'created_by' => Auth::id(),
                'notes' => $notes,
            ]);

            // Update or create summary
            $summary = InventorySummary::firstOrCreate(
                [
                    'item_id' => $itemId,
                    'location_id' => $locationId,
                ],
                [
                    'qty_on_hand' => 0,
                    'qty_allocated' => 0,
                ]
            );

            $summary->increment('qty_on_hand', $quantity);

            return $movement;
        }, attempts: 3);
    }

    /**
     * Transfer stock between locations
     * 🔒 Locks both locations to prevent deadlock
     */
    public function transferStock(
        string $itemId,
        string $fromLocationId,
        string $toLocationId,
        int|float $quantity,
        ?string $notes = null
    ): array {
        return DB::transaction(function () use (
            $itemId, $fromLocationId, $toLocationId, $quantity, $notes
        ) {
            // Lock in consistent order (by ID)
            $ids = collect([$fromLocationId, $toLocationId])->sort();
            $summaries = [];

            foreach ($ids as $locationId) {
                $summary = InventorySummary::where('item_id', $itemId)
                    ->where('location_id', $locationId)
                    ->lockForUpdate()
                    ->first();

                if (! $summary) {
                    throw new \Exception("Inventory summary not found for location {$locationId}");
                }

                $summaries[$locationId] = $summary;
            }

            // Validate source has sufficient stock
            $available = $summaries[$fromLocationId]->qty_on_hand - $summaries[$fromLocationId]->qty_allocated;
            if ($available < $quantity) {
                throw new InsufficientStockException('Insufficient stock at source location');
            }

            // Create OUT movement from source
            $outMovement = InventoryMovement::create([
                'item_id' => $itemId,
                'location_id' => $fromLocationId,
                'movement_type' => 'TRANSFER',
                'quantity' => $quantity,
                'uom_id' => Item::find($itemId)->base_uom_id,
                'reference_doc_type' => 'InternalTransfer',
                'reference_doc_id' => now()->timestamp,
                'created_by' => Auth::id(),
                'notes' => $notes,
            ]);

            // Create IN movement at destination
            $inMovement = InventoryMovement::create([
                'item_id' => $itemId,
                'location_id' => $toLocationId,
                'movement_type' => 'TRANSFER',
                'quantity' => $quantity,
                'uom_id' => Item::find($itemId)->base_uom_id,
                'reference_doc_type' => 'InternalTransfer',
                'reference_doc_id' => now()->timestamp,
                'created_by' => Auth::id(),
                'notes' => $notes,
            ]);

            // Update both summaries
            $summaries[$fromLocationId]->decrement('qty_on_hand', $quantity);
            $summaries[$toLocationId]->increment('qty_on_hand', $quantity);

            return [
                'out_movement' => $outMovement,
                'in_movement' => $inMovement,
            ];
        }, attempts: 3);
    }

    /**
     * Allocate stock (reserve for pending order)
     */
    public function allocateStock(
        string $itemId,
        string $locationId,
        int|float $quantity,
        string $refOrderType,
        string $refOrderId
    ): Allocation {
        return DB::transaction(function () use (
            $itemId, $locationId, $quantity, $refOrderType, $refOrderId
        ) {
            $summary = InventorySummary::where('item_id', $itemId)
                ->where('location_id', $locationId)
                ->lockForUpdate()
                ->first();

            if (! $summary) {
                throw new \Exception('Inventory summary not found');
            }

            $available = $summary->qty_on_hand - $summary->qty_allocated;
            if ($available < $quantity) {
                throw new InsufficientStockException(
                    "Insufficient available stock for allocation. Available: {$available}, Requested: {$quantity}"
                );
            }

            // Create allocation record
            $allocation = Allocation::create([
                'item_id' => $itemId,
                'location_id' => $locationId,
                'allocated_qty' => $quantity,
                'reference_order_type' => $refOrderType,
                'reference_order_id' => $refOrderId,
                'status' => 'pending',
            ]);

            // Create ALLOCATION movement
            InventoryMovement::create([
                'item_id' => $itemId,
                'location_id' => $locationId,
                'movement_type' => 'ALLOCATION',
                'quantity' => $quantity,
                'uom_id' => Item::find($itemId)->base_uom_id,
                'reference_doc_type' => $refOrderType,
                'reference_doc_id' => $refOrderId,
                'created_by' => Auth::id(),
            ]);

            // Update summary
            $summary->increment('qty_allocated', $quantity);

            return $allocation;
        }, attempts: 3);
    }

    /**
     * Deallocate stock (cancel reservation)
     */
    public function deallocateStock(
        string $itemId,
        string $locationId,
        int|float $quantity,
        string $refOrderType,
        string $refOrderId
    ): InventoryMovement {
        return DB::transaction(function () use (
            $itemId, $locationId, $quantity, $refOrderType, $refOrderId
        ) {
            $summary = InventorySummary::where('item_id', $itemId)
                ->where('location_id', $locationId)
                ->lockForUpdate()
                ->first();

            if (! $summary) {
                throw new \Exception('Inventory summary not found');
            }

            if ($summary->qty_allocated < $quantity) {
                throw new InsufficientStockException(
                    "Cannot deallocate {$quantity}. Currently allocated: {$summary->qty_allocated}"
                );
            }

            // Create DEALLOCATION movement
            $movement = InventoryMovement::create([
                'item_id' => $itemId,
                'location_id' => $locationId,
                'movement_type' => 'DEALLOCATION',
                'quantity' => $quantity,
                'uom_id' => Item::find($itemId)->base_uom_id,
                'reference_doc_type' => $refOrderType,
                'reference_doc_id' => $refOrderId,
                'created_by' => Auth::id(),
            ]);

            // Update summary
            $summary->decrement('qty_allocated', $quantity);

            return $movement;
        }, attempts: 3);
    }

    /**
     * Adjustment (manual correction)
     */
    public function createAdjustment(
        string $itemId,
        string $locationId,
        int|float $quantity,
        string $reason,
        ?string $notes = null
    ): InventoryMovement {
        return DB::transaction(function () use (
            $itemId, $locationId, $quantity, $reason, $notes
        ) {
            $summary = InventorySummary::where('item_id', $itemId)
                ->where('location_id', $locationId)
                ->lockForUpdate()
                ->first();

            if (! $summary) {
                throw new \Exception('Inventory summary not found');
            }

            // Determine movement type based on adjustment sign
            $movementType = $quantity >= 0 ? 'IN' : 'OUT';
            $absQuantity = abs($quantity);

            $movement = InventoryMovement::create([
                'item_id' => $itemId,
                'location_id' => $locationId,
                'movement_type' => 'ADJUSTMENT',
                'quantity' => $absQuantity,
                'uom_id' => Item::find($itemId)->base_uom_id,
                'reference_doc_type' => 'Adjustment',
                'reference_doc_id' => "{$reason}-".now()->timestamp,
                'created_by' => Auth::id(),
                'notes' => $notes,
            ]);

            // Update summary
            if ($quantity >= 0) {
                $summary->increment('qty_on_hand', $absQuantity);
            } else {
                $summary->decrement('qty_on_hand', $absQuantity);
            }

            return $movement;
        }, attempts: 3);
    }
}
