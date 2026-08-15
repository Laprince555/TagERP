<?php

namespace Modules\HR\Livewire\OrganizationStructure\JobTitles;

use App\Services\NavigationTreeService;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\HR\Models\OrganizationStructure\JobTitle;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Reachable page for the seeded "hr-org-jbt" Application route
 * (hr.organization-structure.job-titles). Thin page shell only — the
 * actual table lives in JobTitlesTable (App\Livewire\DynamicTable\Table subclass).
 */
#[Layout('layouts.app')]
class JobTitlesIndex extends Component
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
        $application = app(NavigationTreeService::class)->getApplicationByCode(JobTitle::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(JobTitle::APPLICATION_CODE);

        return view('hr::livewire.organization-structure.job-titles.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
