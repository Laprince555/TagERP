<?php

namespace Modules\HR\Livewire\OrganizationStructure\JobTitles;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\HR\Models\OrganizationStructure\JobTitle;

/**
 * Reachable page for the seeded "hr-org-jbt" Application route
 * (hr.organization-structure.job-titles). Thin page shell only — the
 * actual table lives in JobTitlesTable (App\Livewire\DynamicTable\Table subclass).
 */
#[Layout('layouts.app')]
class JobTitlesIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return JobTitle::APPLICATION_CODE;
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
