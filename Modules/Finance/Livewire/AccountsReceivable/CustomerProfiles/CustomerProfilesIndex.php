<?php

namespace Modules\Finance\Livewire\AccountsReceivable\CustomerProfiles;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Finance\Models\AccountsReceivable\CustomerProfile;

/**
 * Page shell for the seeded Application route. The table itself lives in
 * CustomerProfilesTable.
 */
#[Layout('layouts.app')]
class CustomerProfilesIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return CustomerProfile::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(CustomerProfile::APPLICATION_CODE);

        return view('finance::livewire.accounts-receivable.customer-profiles.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
