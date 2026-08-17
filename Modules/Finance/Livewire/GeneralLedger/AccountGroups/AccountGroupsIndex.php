<?php

namespace Modules\Finance\Livewire\GeneralLedger\AccountGroups;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Finance\Models\GeneralLedger\AccountGroup;

/**
 * Page shell for the seeded Application route. The table itself lives in
 * AccountGroupsTable.
 */
#[Layout('layouts.app')]
class AccountGroupsIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return AccountGroup::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(AccountGroup::APPLICATION_CODE);

        return view('finance::livewire.general-ledger.account-groups.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
