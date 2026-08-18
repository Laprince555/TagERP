<?php

namespace Modules\Finance\Livewire\AccountsPayable\DeductionGlLinks;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Finance\Models\AccountsPayable\DeductionGlLink;

/**
 * Page shell for the seeded Application route. The table itself lives in
 * DeductionGlLinksTable.
 */
#[Layout('layouts.app')]
class DeductionGlLinksIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return DeductionGlLink::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(DeductionGlLink::APPLICATION_CODE);

        return view('finance::livewire.accounts-payable.deduction-gl-links.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
