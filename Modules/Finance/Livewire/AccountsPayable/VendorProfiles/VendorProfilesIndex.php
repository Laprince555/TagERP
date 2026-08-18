<?php

namespace Modules\Finance\Livewire\AccountsPayable\VendorProfiles;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Finance\Models\AccountsPayable\VendorProfile;

/**
 * Page shell for the seeded Application route. The table itself lives in
 * VendorProfilesTable.
 */
#[Layout('layouts.app')]
class VendorProfilesIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return VendorProfile::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(VendorProfile::APPLICATION_CODE);

        return view('finance::livewire.accounts-payable.vendor-profiles.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
