<?php

namespace Modules\Finance\Livewire\GeneralLedger\Charts;

use App\Livewire\DynamicRecordView\RecordView;
use App\Services\NavigationTreeService;
use App\Support\RecordReference\RecordReferenceAccess;
use Livewire\Attributes\Layout;
use Modules\Finance\Models\GeneralLedger\Chart;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Reachable page for the Chart show route. Thin adapter only.
 */
#[Layout('layouts.app', ['showBreadcrumbs' => false])]
class ChartRecordView extends RecordView
{
    public function boot(): void
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Chart::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
    }

    protected function recordViewKey(): string
    {
        return 'finance.general-ledger.chart';
    }
}
