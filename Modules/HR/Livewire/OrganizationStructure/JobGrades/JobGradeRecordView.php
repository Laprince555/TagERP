<?php

namespace Modules\HR\Livewire\OrganizationStructure\JobGrades;

use App\Livewire\DynamicRecordView\RecordView;
use App\Services\NavigationTreeService;
use App\Support\RecordReference\RecordReferenceAccess;
use Livewire\Attributes\Layout;
use Modules\HR\Models\OrganizationStructure\JobGrade;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Reachable page for the JobGrade show route (hr.organization-structure.job-grades.show).
 * Thin adapter only — behavior lives in App\Livewire\DynamicRecordView\RecordView.
 */
#[Layout('layouts.app', ['showBreadcrumbs' => false])]
class JobGradeRecordView extends RecordView
{
    public function boot(): void
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(JobGrade::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
    }

    protected function recordViewKey(): string
    {
        return 'hr.organization-structure.job-grade';
    }
}
