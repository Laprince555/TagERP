<?php

namespace Modules\HR\Livewire\OrganizationStructure\Branches;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\HR\Models\OrganizationStructure\Branch;

/**
 * Reachable page for the seeded "hr-org-brn" Application route
 * (hr.organization-structure.branches). Thin page shell only — the actual
 * table lives in BranchesTable (App\Livewire\DynamicTable\Table subclass).
 */
#[Layout('layouts.app')]
class BranchesIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return Branch::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(Branch::APPLICATION_CODE);

        return view('hr::livewire.organization-structure.branches.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
