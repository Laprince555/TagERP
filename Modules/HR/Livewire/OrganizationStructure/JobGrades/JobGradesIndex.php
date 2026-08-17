<?php

namespace Modules\HR\Livewire\OrganizationStructure\JobGrades;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\HR\Models\OrganizationStructure\JobGrade;

/**
 * Reachable page for the seeded "hr-org-jbg" Application route
 * (hr.organization-structure.job-grades). Thin page shell only — the
 * actual table lives in JobGradesTable (App\Livewire\DynamicTable\Table subclass).
 */
#[Layout('layouts.app')]
class JobGradesIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return JobGrade::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(JobGrade::APPLICATION_CODE);

        return view('hr::livewire.organization-structure.job-grades.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
