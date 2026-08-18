<?php

namespace Modules\Inventory\Livewire\StockMovements;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Inventory\Models\Allocation;

/**
 * Page shell for the seeded Application route. The table itself lives in
 * AllocationsTable.
 */
#[Layout('layouts.app')]
class AllocationsIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return Allocation::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(Allocation::APPLICATION_CODE);

        return view('inventory::livewire.stock-movements.allocations.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
