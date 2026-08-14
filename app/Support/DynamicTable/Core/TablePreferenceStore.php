<?php

namespace App\Support\DynamicTable\Core;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Storage contract for per-user table preferences (column visibility, order,
 * per-page, density). NOT for saved views (filters/search/sort) — see
 * SavedTableViewStore for that. Implementations must enforce that a user can
 * only read/write their own preferences.
 */
interface TablePreferenceStore
{
    public function get(Authenticatable $user, string $tableKey): ?array;

    /** @param array<string, mixed> $preferences Raw array shape from TablePreferences::toArray() */
    public function save(Authenticatable $user, string $tableKey, array $preferences): void;

    public function delete(Authenticatable $user, string $tableKey): void;
}
