<?php

namespace Modules\Finance\Livewire\GeneralLedger\FiscalYears;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Finance\Models\GeneralLedger\FiscalYear;

/**
 * Page shell for the seeded Application route. The table itself lives in
 * FiscalYearsTable.
 */
#[Layout('layouts.app')]
class FiscalYearsIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return FiscalYear::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(FiscalYear::APPLICATION_CODE);

        return view('finance::livewire.general-ledger.fiscal-years.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
