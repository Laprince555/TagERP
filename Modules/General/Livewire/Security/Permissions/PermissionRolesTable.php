<?php

namespace Modules\General\Livewire\Security\Permissions;

use App\Livewire\DynamicTable\Table;
use App\Models\Role;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Sort;
use Illuminate\Database\Eloquent\Builder;

/**
 * Embedded-only: which Roles grant this permission — the mirror of
 * RolePermissionsTable, shown as the "Roles" tab on a Permission's record
 * view. Never a standalone Application route.
 */
class PermissionRolesTable extends Table
{
    protected string $tableKey = 'general-security-permission-roles';

    protected ?string $model = Role::class;

    protected function query(): Builder
    {
        return Role::query();
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name')->sortable()->searchable()->label('Role'),
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
