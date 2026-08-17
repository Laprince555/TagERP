<?php

namespace Modules\HR\Livewire\OrganizationStructure\JobTitles;

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
use Modules\HR\Models\OrganizationStructure\JobTitle;

/**
 * Real production Job Titles index for the "hr-org-jbt" Application
 * (hr.organization-structure.job-titles), reusing the Dynamic Table engine.
 */
class JobTitlesTable extends Table
{
    protected string $tableKey = 'hr-organization-structure-job-titles';

    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return JobTitle::APPLICATION_CODE;
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(JobTitle::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return JobTitle::query()->whereKey(-1);
        }

        return JobTitle::query()->with('department');
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name')->sortable()->searchable()->label('Name'),
            RecordReferenceColumn::make('department')
                ->applicationCode('hr-org-dep')
                ->relation('department')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Department'),
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
        return 'hr.organization-structure.job-title.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Job Title');
    }
}
