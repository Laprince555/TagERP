<?php

namespace Modules\HR\Livewire\OrganizationStructure\JobTitles;

use App\Livewire\DynamicRecordView\RecordView;
use App\Services\NavigationTreeService;
use App\Support\RecordReference\RecordReferenceAccess;
use Livewire\Attributes\Layout;
use Modules\HR\Models\OrganizationStructure\JobTitle;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Reachable page for the JobTitle show route (hr.organization-structure.job-titles.show).
 * Thin adapter only — behavior lives in App\Livewire\DynamicRecordView\RecordView.
 */
#[Layout('layouts.app', ['showBreadcrumbs' => false])]
class JobTitleRecordView extends RecordView
{
    public function boot(): void
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(JobTitle::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
    }

    protected function recordViewKey(): string
    {
        return 'hr.organization-structure.job-title';
    }
}
