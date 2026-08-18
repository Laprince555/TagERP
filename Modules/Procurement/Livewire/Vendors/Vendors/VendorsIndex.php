<?php

namespace Modules\Procurement\Livewire\Vendors\Vendors;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Procurement\Models\Vendors\Vendor;

/**
 * Page shell for the seeded Application route. The table itself lives in
 * VendorsTable.
 */
#[Layout('layouts.app')]
class VendorsIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return Vendor::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(Vendor::APPLICATION_CODE);

        return view('procurement::livewire.vendors.vendors.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
