<?php

namespace Modules\Finance\Livewire\AccountsPayable\InvoiceTypes;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Finance\Models\AccountsPayable\ApInvoiceType;

/**
 * Page shell for the seeded Application route. The table itself lives in
 * InvoiceTypesTable.
 */
#[Layout('layouts.app')]
class InvoiceTypesIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return ApInvoiceType::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(ApInvoiceType::APPLICATION_CODE);

        return view('finance::livewire.accounts-payable.invoice-types.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
