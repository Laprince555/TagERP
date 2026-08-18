<?php

namespace Modules\Finance\Livewire\Tax\TaxAdjustments;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Finance\Models\Tax\TaxAdjustment;

/**
 * Page shell for the seeded Application route. The table itself lives in
 * TaxAdjustmentsTable.
 */
#[Layout('layouts.app')]
class TaxAdjustmentsIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return TaxAdjustment::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(TaxAdjustment::APPLICATION_CODE);

        return view('finance::livewire.tax.tax-adjustments.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
