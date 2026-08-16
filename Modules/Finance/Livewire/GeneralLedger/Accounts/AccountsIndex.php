<?php

namespace Modules\Finance\Livewire\GeneralLedger\Accounts;

use App\Services\NavigationTreeService;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Finance\Models\GeneralLedger\Account;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Page shell for the seeded "fin-gl-acc" Application route. The table itself
 * lives in AccountsTable.
 */
#[Layout('layouts.app')]
class AccountsIndex extends Component
{
    public function boot(): void
    {
        $this->checkAccess();
    }

    public function hydrate(): void
    {
        $this->checkAccess();
    }

    protected function checkAccess(): void
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Account::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
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
