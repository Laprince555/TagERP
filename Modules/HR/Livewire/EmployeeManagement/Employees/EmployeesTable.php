<?php

namespace Modules\HR\Livewire\EmployeeManagement\Employees;

use App\Livewire\DynamicTable\Table;
use App\Services\NavigationTreeService;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceAccess;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\HR\Models\EmployeeManagement\Employee;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Real production Employees index for the "hr-emp-emp" Application
 * (hr.employee-management.employees), reusing the Dynamic Table engine.
 */
class EmployeesTable extends Table
{
    protected string $tableKey = 'hr-employee-management-employees';

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

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Employee::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Employee::query()->whereRaw('1 = 0');
        }

        // One query per relation instead of one per relation per row — the
        // reference columns below resolve each of these on every row.
        return Employee::query()->with(['person', 'entity', 'branch', 'department', 'jobTitle']);
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('employee_number')->sortable()->searchable()->label('Employee Number'),
            RecordReferenceColumn::make('person')
                ->applicationCode('gen-wld-per')
                ->relation('person')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Person'),
            RecordReferenceColumn::make('entity')
                ->applicationCode('hr-org-ent')
                ->relation('entity')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Entity'),
            RecordReferenceColumn::make('branch')
                ->applicationCode('hr-org-brn')
                ->relation('branch')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Branch'),
            RecordReferenceColumn::make('department')
                ->applicationCode('hr-org-dep')
                ->relation('department')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Department'),
            RecordReferenceColumn::make('jobTitle')
                ->applicationCode('hr-org-jbt')
                ->relation('jobTitle')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Job Title'),
            TextColumn::make('status')
                ->sortable()
                ->label('Status')
                ->formatUsing(fn (mixed $value): string => ucfirst((string) $value)),
        ];
    }

    protected function filters(): array
    {
        return [
            TextFilter::make('employee_number'),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('employee_number')->ascending()];
    }

    protected function createForm(): ?string
    {
        return 'hr.employee-management.employee.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Employee');
    }
}
