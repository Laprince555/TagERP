<?php

namespace Modules\HR\Livewire\OrganizationStructure\Departments;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Livewire\DynamicTable\Table;
use App\Services\NavigationTreeService;
use App\Support\DynamicTable\Core\Columns\BooleanColumn;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceAccess;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\HR\Models\OrganizationStructure\Department;

/**
 * Real production Departments index for the "hr-org-dep" Application
 * (hr.organization-structure.departments), reusing the Dynamic Table engine.
 */
class DepartmentsTable extends Table
{
    protected string $tableKey = 'hr-organization-structure-departments';

    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return Department::APPLICATION_CODE;
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Department::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Department::query()->whereKey(-1);
        }

        return Department::query()->with('parent');
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name')->sortable()->searchable()->label('Name'),
            RecordReferenceColumn::make('parent')
                ->applicationCode('hr-org-dep')
                ->relation('parent')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Parent Department'),
            BooleanColumn::make('is_active')->sortable()->label('Active'),
        ];
    }

    protected function filters(): array
    {
        return [
            TextFilter::make('name'),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('name')->ascending()];
    }

    protected function createForm(): ?string
    {
        return 'hr.organization-structure.department.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Department');
    }
}
