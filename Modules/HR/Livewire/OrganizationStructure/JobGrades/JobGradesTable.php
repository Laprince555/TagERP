<?php

namespace Modules\HR\Livewire\OrganizationStructure\JobGrades;

use App\Livewire\DynamicTable\Table;
use App\Services\NavigationTreeService;
use App\Support\DynamicTable\Core\Columns\BooleanColumn;
use App\Support\DynamicTable\Core\Columns\NumberColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\HR\Models\OrganizationStructure\JobGrade;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Real production Job Grades index for the "hr-org-jbg" Application
 * (hr.organization-structure.job-grades), reusing the Dynamic Table engine.
 */
class JobGradesTable extends Table
{
    protected string $tableKey = 'hr-organization-structure-job-grades';

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
        $application = app(NavigationTreeService::class)->getApplicationByCode(JobGrade::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(JobGrade::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return JobGrade::query()->whereRaw('1 = 0');
        }

        return JobGrade::query();
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name')->sortable()->searchable()->label('Name'),
            NumberColumn::make('level')->sortable()->label('Level'),
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
        return [Sort::make('level')->ascending()];
    }

    protected function createForm(): ?string
    {
        return 'hr.organization-structure.job-grade.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Job Grade');
    }
}
