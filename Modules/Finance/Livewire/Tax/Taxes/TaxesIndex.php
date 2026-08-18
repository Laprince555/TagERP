<?php

namespace Modules\Finance\Livewire\Tax\Taxes;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Finance\Models\Tax\Tax;

/**
 * Page shell for the seeded Application route. The table itself lives in
 * TaxesTable.
 */
#[Layout('layouts.app')]
class TaxesIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return Tax::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(Tax::APPLICATION_CODE);

        return view('finance::livewire.tax.taxes.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
