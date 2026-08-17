<?php

namespace Modules\Finance\Livewire\GeneralLedger\CostCenters;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Finance\Models\GeneralLedger\CostCenter;

/**
 * Page shell for the seeded Application route. The table itself lives in
 * CostCentersTable.
 */
#[Layout('layouts.app')]
class CostCentersIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return CostCenter::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(CostCenter::APPLICATION_CODE);

        return view('finance::livewire.general-ledger.cost-centers.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
