<?php

namespace Modules\General\Livewire\Security\Roles;

use App\Livewire\DynamicTable\Table;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Sort;
use Illuminate\Database\Eloquent\Builder;
use Modules\HR\Models\OrganizationStructure\JobTitle;

/**
 * Embedded-only: which job titles (job_title_grade_roles) this Role is
 * attached to — the "Job Titles" tab on a Role's record view. Never a
 * standalone Application route.
 */
class RoleJobTitlesTable extends Table
{
    protected string $tableKey = 'general-security-role-job-titles';

    protected ?string $model = JobTitle::class;

    protected function query(): Builder
    {
        return JobTitle::query();
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name')->sortable()->searchable()->label('Job Title'),
        ];
    }

    protected function filters(): array
    {
        return [];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('name')->ascending()];
    }
}
