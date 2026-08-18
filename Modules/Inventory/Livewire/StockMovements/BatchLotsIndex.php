<?php

namespace Modules\Inventory\Livewire\StockMovements;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Inventory\Models\BatchLot;

/**
 * Page shell for the seeded Application route. The table itself lives in
 * BatchLotsTable.
 */
#[Layout('layouts.app')]
class BatchLotsIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return BatchLot::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(BatchLot::APPLICATION_CODE);

        return view('inventory::livewire.stock-movements.batch-lots.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
