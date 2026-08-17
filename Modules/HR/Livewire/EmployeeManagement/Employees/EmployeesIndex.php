<?php

namespace Modules\HR\Livewire\EmployeeManagement\Employees;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\HR\Models\EmployeeManagement\Employee;

/**
 * Reachable page for the seeded "hr-emp-emp" Application route
 * (hr.employee-management.employees). Thin page shell only — the actual
 * table lives in EmployeesTable (App\Livewire\DynamicTable\Table subclass).
 */
#[Layout('layouts.app')]
class EmployeesIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return Employee::APPLICATION_CODE;
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
