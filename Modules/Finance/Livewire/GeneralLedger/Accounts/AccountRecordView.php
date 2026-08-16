<?php

namespace Modules\Finance\Livewire\GeneralLedger\Accounts;

use App\Livewire\DynamicRecordView\RecordView;
use App\Services\NavigationTreeService;
use App\Support\RecordReference\RecordReferenceAccess;
use Livewire\Attributes\Layout;
use Modules\Finance\Models\GeneralLedger\Account;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Reachable page for the Account show route. Thin adapter only.
 */
#[Layout('layouts.app', ['showBreadcrumbs' => false])]
class AccountRecordView extends RecordView
{
    public function boot(): void
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Account::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
    }

    protected function recordViewKey(): string
    {
        return 'finance.general-ledger.account';
    }
}
