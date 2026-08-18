<?php

namespace Modules\Inventory\Livewire\Warehousing;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Inventory\Models\CycleCount;

/**
 * Page shell for the seeded Application route. The table itself lives in
 * CycleCountsTable.
 */
#[Layout('layouts.app')]
class CycleCountsIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return CycleCount::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(CycleCount::APPLICATION_CODE);

        return view('inventory::livewire.warehousing.cycle-counts.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
