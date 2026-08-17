<?php

namespace Modules\Finance\Livewire\GeneralLedger\Charts;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Finance\Models\GeneralLedger\Chart;

/**
 * Page shell for the seeded "fin-gl-coa" Application route. The table itself
 * lives in ChartsTable.
 */
#[Layout('layouts.app')]
class ChartsIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return Chart::APPLICATION_CODE;
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
