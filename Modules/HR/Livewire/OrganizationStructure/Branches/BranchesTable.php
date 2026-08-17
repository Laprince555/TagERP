<?php

namespace Modules\HR\Livewire\OrganizationStructure\Branches;

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
use Modules\HR\Models\OrganizationStructure\Branch;

/**
 * Real production Branches index for the "hr-org-brn" Application
 * (hr.organization-structure.branches), reusing the Dynamic Table engine.
 */
class BranchesTable extends Table
{
    protected string $tableKey = 'hr-organization-structure-branches';

    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return Branch::APPLICATION_CODE;
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Branch::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Branch::query()->whereKey(-1);
        }

        return Branch::query()->with('entity');
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name')->sortable()->searchable()->label('Name'),
            RecordReferenceColumn::make('entity')
                ->applicationCode('hr-org-ent')
                ->relation('entity')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Entity'),
            BooleanColumn::make('is_main')->sortable()->label('Main Branch'),
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
        return 'hr.organization-structure.branch.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Branch');
    }
}
