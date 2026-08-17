<?php

namespace Modules\Finance\Livewire\GeneralLedger\Accounts;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Finance\Models\GeneralLedger\Account;

/**
 * Page shell for the seeded "fin-gl-acc" Application route. The table itself
 * lives in AccountsTable.
 */
#[Layout('layouts.app')]
class AccountsIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return Account::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(Account::APPLICATION_CODE);

        return view('finance::livewire.general-ledger.accounts.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
