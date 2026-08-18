<?php

namespace Modules\Inventory\Livewire\Warehousing;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Inventory\Models\Warehouse;

/**
 * Page shell for the seeded Application route. The table itself lives in
 * WarehousesTable.
 */
#[Layout('layouts.app')]
class WarehousesIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return Warehouse::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(Warehouse::APPLICATION_CODE);

        return view('inventory::livewire.warehousing.warehouses.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
