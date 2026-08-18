<?php

namespace Modules\Inventory\Livewire\StockMovements;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Inventory\Models\InventoryMovement;

/**
 * Page shell for the seeded Application route. The table itself lives in
 * InventoryMovementsTable.
 */
#[Layout('layouts.app')]
class InventoryMovementsIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return InventoryMovement::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(InventoryMovement::APPLICATION_CODE);

        return view('inventory::livewire.stock-movements.inventory-movements.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
