<?php

namespace Modules\General\Livewire\Security\Rules;

use App\Livewire\DynamicTable\Table;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Sort;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Permission;

/**
 * Embedded-only: which permissions (role_has_permissions, via Spatie's own
 * Role::permissions() relation) this Rule grants — the "Permissions" tab on
 * a Rule's record view. Never a standalone Application route.
 */
class RulePermissionsTable extends Table
{
    protected string $tableKey = 'general-security-rule-permissions';

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
