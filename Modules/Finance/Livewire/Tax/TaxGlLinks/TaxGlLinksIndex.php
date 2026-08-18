<?php

namespace Modules\Finance\Livewire\Tax\TaxGlLinks;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Finance\Models\Tax\TaxGlLink;

/**
 * Page shell for the seeded Application route. The table itself lives in
 * TaxGlLinksTable.
 */
#[Layout('layouts.app')]
class TaxGlLinksIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return TaxGlLink::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(TaxGlLink::APPLICATION_CODE);

        return view('finance::livewire.tax.tax-gl-links.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
