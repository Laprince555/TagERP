# Global Search

## Search drivers (✅ Implemented)

Search is pluggable behind `App\Support\DynamicTable\Core\SearchDriver`:

```php
interface SearchDriver
{
    public function search(Builder $query, string $searchTerm, array $searchableColumns): Builder;
}
```

Two implementations exist, both in `App\Support\DynamicTable\Query\SearchDrivers`:

- **`DatabaseSearchDriver`** (default) — plain SQL `LIKE`, described below. Used automatically
  when a table doesn't override `Table::searchDriver()`.
- **`ScoutSearchDriver`** — delegates to Laravel Scout. Asks Scout for matching primary keys via
  `$model::search($term)->keys()`, then constrains the **already-scoped** query with
  `whereIn($model->getQualifiedKeyName(), $ids)`. This is the load-bearing security property: Scout
  can only *narrow* the result set, it can never widen it past whatever scopes `query()` already
  applied — tenancy/authorization/soft-delete scopes on the base query are preserved exactly as
  with the database driver. Throws `ModelNotSearchableException` if the table's model doesn't use
  `Laravel\Scout\Searchable`.

```php
use App\Support\DynamicTable\Query\SearchDrivers\ScoutSearchDriver;

protected function searchDriver(): ?SearchDriver
{
    return new ScoutSearchDriver();
}
```

Verified in `tests/Feature/DynamicTable/SearchDriverTest.php`, including a positive Scout test
(using Scout's built-in `collection` engine, no external search service needed) that proves a
base-query scope (`where('active', true)`) survives being combined with the Scout-driven `whereIn`.

## How the database driver works

`DatabaseSearchDriver::search()` wraps every searchable column's condition inside a single
nested `where(function ($q) { ... })`, using only `orWhere`/`orWhereHas` **inside** that closure.
This guarantees the search OR-group can never escape the base query's scopes — it's structurally
impossible for search to widen a tenant/company/permission scope defined in `query()`.

```sql
select ... from users where (base scopes here) and (name like ? or email like ? or ...)
```

## Searchable columns only

A column participates in search only if `->searchable()` was called:

```php
TextColumn::make('name')->searchable();          // searches `name`
TextColumn::make('name')->searchable(['nickname']); // also searches `nickname`
RelationColumn::make('country.name')->searchable(); // searches via whereHas('country', ...)
```

Non-searchable columns are never touched. Unauthorized (`visible(false)`) columns are excluded
from the searchable set entirely, regardless of their `searchable()` flag.

## Relation search

`RelationColumn`s use `orWhereHas($relationPath, ...)` — the relation path always comes from the
**server-defined** column, never from client input. There is no way for a client to specify an
arbitrary relation name to search.

## Request behavior

Search is **submit-triggered** (Enter key or the Search button), not live-as-you-type. The
Blade partial binds `wire:model="search"` (no `.live`) with `wire:submit="submitSearch"`. This
matches the request-minimization requirement: one Livewire request per search, not one per
keystroke.

If a future table needs live search, the binding must be changed to
`wire:model.live.debounce.400ms` (or slower) — 400ms is the documented minimum. This is called
out as a code comment on `Table::submitSearch()`.

## Security

- **Parameter bindings only.** Search text is passed through Eloquent's `where(..., 'like', $value)`
  — never string-interpolated into SQL.
- **LIKE wildcard escaping.** `%` and `_` (and `\`) in the search text are escaped with
  `addcslashes($text, '\\%_')` before being wrapped in `%...%`, so a user searching for a literal
  `%` or `_` gets literal matching, not wildcard behavior.
- **Length clamped.** `TableState::MAX_SEARCH_LENGTH = 200` — anything longer is truncated by
  `TableState::normalizeSearch()` before it ever reaches a query.
- **Trimmed and type-checked.** Non-string search input is coerced to `''`.

## Unicode & Arabic

Search text is handled as UTF-8 throughout — `mb_substr()` is used for length clamping (not
`substr()`, which would corrupt multi-byte characters). Arabic and other Unicode text round-trips
through `LIKE` correctly on the (SQLite/MySQL) database backends this engine has been tested
against. See the `'relation search does not duplicate parent rows'` and Arabic-text dataset cases
in `tests/Feature/DynamicTable/QueryEngineTest.php`.

## Pagination reset

Any change to `search` (via `submitSearch()`) resets `page` to `1` — see
[sorting-pagination.md](sorting-pagination.md#pagination-reset-behavior).
