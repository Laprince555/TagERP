<?php

namespace Modules\Inventory\Livewire\Components;

use Illuminate\Support\Collection;
use Livewire\Attributes\Reactive;
use Livewire\Component;
use Modules\Inventory\Models\InventorySummary;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Services\StockMovements\StockQueryService;

class StockDashboard extends Component
{
    #[Reactive]
    public ?string $warehouseId = null;

    #[Reactive]
    public string $period = '30'; // days

    protected StockQueryService $stockService;

    public function mount()
    {
        $this->stockService = app(StockQueryService::class);

        if (! $this->warehouseId) {
            $this->warehouseId = auth()->user()->branch->warehouses()->first()?->id;
        }
    }

    public function render()
    {
        return view('inventory::livewire.components.stock-dashboard', [
            'warehouse' => Warehouse::find($this->warehouseId),
            'totalItems' => $this->getTotalItems(),
            'lowStockItems' => $this->getLowStockItems(),
            'overStockedItems' => $this->getOverstockedItems(),
            'topMovers' => $this->getTopMovers(),
            'totalInventoryValue' => $this->getTotalInventoryValue(),
            'statistics' => $this->getStatistics(),
        ]);
    }

    private function getTotalItems(): int
    {
        if (! $this->warehouseId) {
            return 0;
        }

        return InventorySummary::whereHas('location', fn ($q) => $q->where('warehouse_id', $this->warehouseId)
        )->distinct('item_id')->count();
    }

    private function getLowStockItems(): Collection
    {
        if (! $this->warehouseId) {
            return collect();
        }

        return InventorySummary::whereHas('location', fn ($q) => $q->where('warehouse_id', $this->warehouseId)
        )
            ->whereRaw('qty_on_hand <= items.reorder_point')
            ->with(['item', 'location'])
            ->orderBy('qty_on_hand', 'ASC')
            ->limit(10)
            ->get();
    }

    private function getOverstockedItems(): Collection
    {
        if (! $this->warehouseId) {
            return collect();
        }

        return InventorySummary::whereHas('location', fn ($q) => $q->where('warehouse_id', $this->warehouseId)
        )
            ->whereRaw('qty_on_hand > items.reorder_qty * 2')
            ->with(['item', 'location'])
            ->orderBy('qty_on_hand', 'DESC')
            ->limit(10)
            ->get();
    }

    private function getTopMovers(): Collection
    {
        if (! $this->warehouseId) {
            return collect();
        }

        return InventorySummary::whereHas('location', fn ($q) => $q->where('warehouse_id', $this->warehouseId)
        )
            ->with(['item'])
            ->get()
            ->map(function ($summary) {
                $velocity = $this->stockService->getStockVelocity(
                    $summary->item_id,
                    $summary->location_id,
                    (int) $this->period
                );

                return [
                    'item' => $summary->item,
                    'velocity' => $velocity,
                    'days_supply' => $velocity > 0 ? $summary->qty_on_hand / $velocity : 0,
                ];
            })
            ->sortByDesc('velocity')
            ->take(10);
    }

    private function getTotalInventoryValue(): float
    {
        if (! $this->warehouseId) {
            return 0;
        }

        return InventorySummary::whereHas('location', fn ($q) => $q->where('warehouse_id', $this->warehouseId)
        )
            ->get()
            ->sum(fn ($summary) => $summary->qty_on_hand * ($summary->item->last_cost_per_unit ?? 0));
    }

    private function getStatistics(): array
    {
        if (! $this->warehouseId) {
            return [
                'total_on_hand' => 0,
                'total_allocated' => 0,
                'total_available' => 0,
                'utilization_percent' => 0,
            ];
        }

        $summaries = InventorySummary::whereHas('location', fn ($q) => $q->where('warehouse_id', $this->warehouseId)
        )->get();

        $totalOnHand = $summaries->sum('qty_on_hand');
        $totalAllocated = $summaries->sum('qty_allocated');
        $warehouse = Warehouse::find($this->warehouseId);
        $capacity = $warehouse->locations()->sum('capacity_units');

        return [
            'total_on_hand' => number_format($totalOnHand, 2),
            'total_allocated' => number_format($totalAllocated, 2),
            'total_available' => number_format($totalOnHand - $totalAllocated, 2),
            'utilization_percent' => $capacity > 0 ? round(($totalOnHand / $capacity) * 100, 1) : 0,
        ];
    }
}
