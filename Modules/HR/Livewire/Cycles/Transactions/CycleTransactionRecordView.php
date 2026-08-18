<?php

namespace Modules\HR\Livewire\Cycles\Transactions;

use App\Livewire\DynamicRecordView\RecordView;
use App\Services\NavigationTreeService;
use App\Support\RecordReference\RecordReferenceAccess;
use Livewire\Attributes\Layout;
use Modules\HR\Models\Cycles\CycleTransaction;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Reachable page for the CycleTransaction show route
 * (hr.cycles.transactions.show). Thin adapter only — behavior lives in
 * App\Livewire\DynamicRecordView\RecordView.
 */
#[Layout('layouts.app', ['showBreadcrumbs' => false])]
class CycleTransactionRecordView extends RecordView
{
    public function boot(): void
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(CycleTransaction::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
    }

    protected function recordViewKey(): string
    {
        return 'hr.cycles.transaction';
    }
}
