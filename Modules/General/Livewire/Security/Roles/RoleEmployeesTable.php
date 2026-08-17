<?php

namespace Modules\General\Livewire\Security\Roles;

use App\Livewire\DynamicTable\Table;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\HR\Models\EmployeeManagement\Employee;

/**
 * Embedded-only: which employees (employee_roles) this Role is granted to
 * directly, as an exception on top of their job title — the "Employees" tab
 * on a Role's record view. Never a standalone Application route.
 */
class RoleEmployeesTable extends Table
{
    protected string $tableKey = 'general-security-role-employees';

    protected ?string $model = Employee::class;

    protected function query(): Builder
    {
        return Employee::query()->with('person');
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
        ];
    }

    protected function filters(): array
    {
        return [];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('employee_number')->ascending()];
    }
}
