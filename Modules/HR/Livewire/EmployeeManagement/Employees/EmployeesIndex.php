<?php

namespace Modules\HR\Livewire\EmployeeManagement\Employees;

use App\Services\NavigationTreeService;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\HR\Models\EmployeeManagement\Employee;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Reachable page for the seeded "hr-emp-emp" Application route
 * (hr.employee-management.employees). Thin page shell only — the actual
 * table lives in EmployeesTable (App\Livewire\DynamicTable\Table subclass).
 */
#[Layout('layouts.app')]
class EmployeesIndex extends Component
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
        $application = app(NavigationTreeService::class)->getApplicationByCode(Employee::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(Employee::APPLICATION_CODE);

        return view('hr::livewire.employee-management.employees.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
