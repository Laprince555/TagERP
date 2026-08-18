<?php

namespace Modules\HR\Livewire\Cycles\CycleTypes;

use App\Livewire\DynamicRecordView\RecordView;
use App\Services\NavigationTreeService;
use App\Support\RecordReference\RecordReferenceAccess;
use Livewire\Attributes\Layout;
use Modules\HR\Models\Cycles\CycleType;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Reachable page for the CycleType show route (hr.cycles.cycle-types.show).
 * Thin adapter only — behavior lives in App\Livewire\DynamicRecordView\RecordView.
 */
#[Layout('layouts.app', ['showBreadcrumbs' => false])]
class CycleTypeRecordView extends RecordView
{
    public function boot(): void
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(CycleType::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
    }

    protected function recordViewKey(): string
    {
        return 'hr.cycles.cycle-type';
    }
}
