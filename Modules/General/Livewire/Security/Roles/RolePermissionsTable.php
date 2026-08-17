<?php

namespace Modules\General\Livewire\Security\Roles;

use App\Livewire\DynamicTable\Table;
use App\Models\Permission;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Sort;
use Illuminate\Database\Eloquent\Builder;

/**
 * Embedded-only: which permissions (role_has_permissions, via Spatie's own
 * Role::permissions() relation) this Role grants — the "Permissions" tab on
 * a Role's record view. Never a standalone Application route.
 */
class RolePermissionsTable extends Table
{
    protected string $tableKey = 'general-security-role-permissions';

    protected ?string $model = Permission::class;

    protected function query(): Builder
    {
        return Permission::query();
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name')->sortable()->searchable()->label('Permission'),
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
