<?php

namespace Modules\HR\Livewire\Cycles\Cycles;

use App\Livewire\DynamicRecordView\RecordView;
use App\Services\NavigationTreeService;
use App\Support\RecordReference\RecordReferenceAccess;
use Livewire\Attributes\Layout;
use Modules\HR\Models\Cycles\Cycle;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Reachable page for the Cycle show route (hr.cycles.cycles.show). Thin
 * adapter only — behavior lives in App\Livewire\DynamicRecordView\RecordView.
 */
#[Layout('layouts.app', ['showBreadcrumbs' => false])]
class CycleRecordView extends RecordView
{
    public function boot(): void
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Cycle::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
    }

    protected function recordViewKey(): string
    {
        return 'hr.cycles.cycle';
    }
}
