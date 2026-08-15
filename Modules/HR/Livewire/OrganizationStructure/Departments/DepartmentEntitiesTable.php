<?php

namespace Modules\HR\Livewire\OrganizationStructure\Departments;

use App\Livewire\DynamicTable\Table;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\HR\Models\OrganizationStructure\Entity;

/**
 * Embedded-only: which entities (department_entity) currently have this
 * department active — the "Companies" tab on a Department's record view.
 * Never a standalone Application route, so no access gate of its own — the
 * parent Department's query() already gates the page this is embedded on.
 */
class DepartmentEntitiesTable extends Table
{
    protected string $tableKey = 'hr-organization-structure-department-entities';

    protected ?string $model = Entity::class;

    protected function query(): Builder
    {
        return Entity::query();
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name')->sortable()->searchable()->label('Name'),
            RecordReferenceColumn::make('company')
                ->applicationCode('gen-wld-com')
                ->relation('company')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Company'),
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
