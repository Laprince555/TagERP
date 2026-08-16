<?php

namespace Modules\Finance\Livewire\GeneralLedger\Charts;

use App\Services\NavigationTreeService;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Finance\Models\GeneralLedger\Chart;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Page shell for the seeded "fin-gl-coa" Application route. The table itself
 * lives in ChartsTable.
 */
#[Layout('layouts.app')]
class ChartsIndex extends Component
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
        $application = app(NavigationTreeService::class)->getApplicationByCode(Chart::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(Chart::APPLICATION_CODE);

        return view('finance::livewire.general-ledger.charts.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
