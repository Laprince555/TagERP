<?php

namespace Modules\Finance\Livewire\GeneralLedger\Journals;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Finance\Models\GeneralLedger\Journal;

/**
 * Page shell for the seeded Application route. The table itself lives in
 * JournalsTable.
 */
#[Layout('layouts.app')]
class JournalsIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return Journal::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(Journal::APPLICATION_CODE);

        return view('finance::livewire.general-ledger.journals.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
