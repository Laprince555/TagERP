<?php

namespace Modules\HR\Livewire\OrganizationStructure\JobTitles;

use App\Livewire\DynamicTable\Table;
use App\Support\DynamicTable\Core\Columns\NumberColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Sort;
use Illuminate\Database\Eloquent\Builder;
use Modules\HR\Models\OrganizationStructure\JobGrade;

/**
 * Embedded-only: which grades (job_title_grade) this job title allows — the
 * "Grades" tab on a Job Title's record view. Never a standalone Application
 * route, so no access gate of its own — the parent Job Title's query()
 * already gates the page this is embedded on.
 */
class JobTitleGradesTable extends Table
{
    protected string $tableKey = 'hr-organization-structure-job-title-grades';

    protected ?string $model = JobGrade::class;

    protected function query(): Builder
    {
        return JobGrade::query();
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name')->sortable()->searchable()->label('Name'),
            NumberColumn::make('level')->sortable()->label('Level'),
        ];
    }

    protected function filters(): array
    {
        return [];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('level')->ascending()];
    }
}
