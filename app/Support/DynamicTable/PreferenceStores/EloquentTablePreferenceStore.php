<?php

namespace App\Support\DynamicTable\PreferenceStores;

use App\Models\UserTablePreference;
use App\Support\DynamicTable\Core\TablePreferenceStore;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Eloquent-backed preference storage. A user can only ever read/write rows
 * scoped to their own user_id — every query is constrained by the given
 * $user, never by a client-supplied id.
 */
class EloquentTablePreferenceStore implements TablePreferenceStore
{
    public function get(Authenticatable $user, string $tableKey): ?array
    {
        $row = UserTablePreference::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->where('table_key', $tableKey)
            ->first();

        return $row?->preferences;
    }

    public function save(Authenticatable $user, string $tableKey, array $preferences): void
    {
        // ponytail: unique(user_id, table_key) makes upsert() race-safe without a manual lock;
        // a concurrent duplicate insert is rejected at the DB constraint and upsert's ON DUPLICATE
        // KEY UPDATE path (or equivalent) resolves it to a single row.
        UserTablePreference::query()->upsert(
            [[
                'user_id' => $user->getAuthIdentifier(),
                'table_key' => $tableKey,
                'preferences' => json_encode($preferences),
                'schema_version' => $preferences['version'] ?? 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]],
            uniqueBy: ['user_id', 'table_key'],
            update: ['preferences', 'schema_version', 'updated_at'],
        );
    }

    public function delete(Authenticatable $user, string $tableKey): void
    {
        UserTablePreference::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->where('table_key', $tableKey)
            ->delete();
    }
}
