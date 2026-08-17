<?php

namespace Modules\HR\Livewire\OrganizationStructure\Departments;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\HR\Models\OrganizationStructure\Department;

/**
 * Reachable page for the seeded "hr-org-dep" Application route
 * (hr.organization-structure.departments). Thin page shell only — the
 * actual table lives in DepartmentsTable (App\Livewire\DynamicTable\Table subclass).
 */
#[Layout('layouts.app')]
class DepartmentsIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return Department::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(Department::APPLICATION_CODE);

        return view('hr::livewire.organization-structure.departments.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
