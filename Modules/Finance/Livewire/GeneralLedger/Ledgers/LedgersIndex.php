<?php

namespace Modules\Finance\Livewire\GeneralLedger\Ledgers;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Finance\Models\GeneralLedger\Ledger;

/**
 * Page shell for the seeded Application route. The table itself lives in
 * LedgersTable.
 */
#[Layout('layouts.app')]
class LedgersIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return Ledger::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(Ledger::APPLICATION_CODE);

        return view('finance::livewire.general-ledger.ledgers.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
