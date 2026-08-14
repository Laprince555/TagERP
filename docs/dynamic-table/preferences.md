# User Preferences

Automatic, per-user persistence of column visibility/order and per-page — **not** a saved view
(see [saved-views.md](saved-views.md) for filters/search/sort persistence).

## What is persisted (✅ Implemented)

- Column visibility (which toggleable columns are hidden)
- Column order
- Per-page value
- Density (`compact`/`comfortable`/`spacious` — stored, not yet rendered differently by the UI)
- Schema version (for stale-preference normalization)

## What is NOT persisted

- Current page
- Search text
- Filter state

These are temporary, request-scoped state — they live in Livewire's namespaced query-string
params (see `Table::queryString()`), not in the preferences table.

## Database schema

```
user_table_preferences
- id
- user_id           (FK, cascade on delete)
- table_key
- preferences        JSON  — {version, hidden_columns, column_order, per_page, density}
- schema_version     unsigned int
- created_at / updated_at

UNIQUE(user_id, table_key)
INDEX(table_key)
```

`App\Models\UserTablePreference` casts `preferences` to an array and `schema_version` to an int.

## Storage contract

```php
interface TablePreferenceStore
{
    public function get(Authenticatable $user, string $tableKey): ?array;
    public function save(Authenticatable $user, string $tableKey, array $preferences): void;
    public function delete(Authenticatable $user, string $tableKey): void;
}
```

Bound to `App\Support\DynamicTable\PreferenceStores\EloquentTablePreferenceStore` in
`AppServiceProvider`. Every query is scoped to the given `$user`'s own id — there is no code path
that accepts a client-supplied user id.

## Ownership

- `get`/`save`/`delete` always filter by `$user->getAuthIdentifier()` — a user can only ever read
  or write their own row.
- Guests (`!auth()->check()`) never trigger a read or write; the table falls back to definition
  defaults for the request.

## Concurrency

`save()` uses `upsert(..., uniqueBy: ['user_id', 'table_key'])`, which relies on the table's own
unique constraint — two concurrent saves for the same user/table resolve to one row via the
database's own conflict resolution, never a duplicate-row race.

## Load-once guarantee

Preferences are read exactly once, in `Table::mount()` — never re-queried during `render()`.
Verified by `tests/Feature/DynamicTable/PreferencesTest.php`'s
`'preferences load once during mount and are not re-queried on subsequent renders'` test (asserts
zero `user_table_preferences` queries after the initial mount).

## Reset to default

`Table::resetPreferences()` deletes the stored row and re-derives `TablePreferences::normalize()`
against the live table definition (i.e. defaults) — one delete query, no leftover state.

## Schema versioning & stale-preference normalization

`TablePreferences::normalize(TableDefinition $definition, ?array $raw)` is the single point every
stored (or absent) preference payload passes through before being applied:

| Scenario | Behavior |
|---|---|
| No stored row (`$raw === null`) | Seeds `hidden_columns` from every `->hiddenByDefault()` column |
| A column was added to the definition since last save | Appended to `column_order` in definition order, visible by default |
| A column was removed from the definition | Dropped from both `hidden_columns` and `column_order` |
| A column's `visible()` now returns `false` | Dropped from both, exactly like a removed column |
| Duplicate keys in stored `column_order` | Deduplicated |
| A fixed (`toggleable(false)`) column | Always kept in its defined relative position, never in `hidden_columns` |
| `per_page` outside the allowlist | Clamped to the allowlist |
| Invalid `density` value | Falls back to `'comfortable'` |
| Any `schema_version` | Bumped to the current version (`1`) after normalization |

This means a stale preferences row from before a table definition changed can never crash
rendering or leak a since-removed/unauthorized column — it's silently repaired on next load.
