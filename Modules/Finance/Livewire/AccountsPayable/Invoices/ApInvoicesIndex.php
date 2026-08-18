<?php

namespace Modules\Finance\Livewire\AccountsPayable\Invoices;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Finance\Models\AccountsPayable\ApInvoice;

/**
 * Page shell for the seeded Application route. The table itself lives in
 * ApInvoicesTable.
 */
#[Layout('layouts.app')]
class ApInvoicesIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return ApInvoice::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(ApInvoice::APPLICATION_CODE);

        return view('finance::livewire.accounts-payable.invoices.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
