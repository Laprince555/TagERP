<?php

namespace Modules\Finance\Livewire\GeneralLedger\CostCenters;

use App\Services\NavigationTreeService;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Finance\Models\GeneralLedger\CostCenter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Page shell for the seeded Application route. The table itself lives in
 * CostCentersTable.
 */
#[Layout('layouts.app')]
class CostCentersIndex extends Component
{
    public function boot(): void
    {
        $this->checkAccess();
    }

    public function hydrate(): void
    {
        $this->checkAccess();
    }

    protected function checkAccess(): void
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(CostCenter::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
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
