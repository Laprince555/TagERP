# Performance

## Expected Livewire request behavior

| Interaction | Requests |
|---|---|
| Typing in search | 0 (not live — see [search.md](search.md)) |
| Submitting search | 1 |
| Changing N filter fields, then Apply | 1 (deferred `wire:model`, applied on `applyFilters()`) |
| Clicking Clear / Clear all | 1 |
| Clicking a sortable header | 1 |
| Changing per-page | 1 |
| Toggling a column | 1 (and one preference write) |
| Completing a column drag-drop reorder | 1 (and one preference write per drop, not per pointer move) |
| Applying a saved view | 1 |
| Saving current state as a view | 1 |

Never one request per keystroke, per row, or per cell. There is exactly **one Livewire component
per table instance** — never one component per row or per cell.

## Query-count expectations (no N+1)

Query count is verified to stay **constant** as the row count grows, both with and without
relations:

- `tests/Feature/DynamicTable/QueryEngineTest.php::'query count stays constant regardless of result set size (no N+1)'`
  — pages 5 vs 100 rows through an eager-loaded `BelongsTo` relation, asserts identical query count.
- `tests/Feature/DynamicTable/PreferencesTest.php::'preferences load once during mount...'` —
  preference storage is read exactly once per request, never per row.
- `tests/Feature/DynamicTable/PerformanceRegressionTest.php` — simple pagination issues zero
  `count()` queries; standard pagination issues exactly one.

## Preventing N+1

- `applySelect()` builds an explicit column list, never `select(*)`.
- `applyEagerLoads()` calls `with()` only for relation paths behind currently visible+authorized
  `RelationColumn`s — a hidden or unauthorized relation column never triggers an eager load.
- Relation filters/search use `whereHas()`/`whereRelation()` (exists-based, correlated subqueries)
  rather than joins — this also prevents duplicate parent rows from to-many relation matches.

## Column selection

`applySelect()` always includes the model's primary key plus every visible+authorized column's
underlying field, plus the foreign key for any visible first-level `BelongsTo` relation column (so
eager loading can match rows). Hidden/unauthorized columns' fields are never selected.

## Relation eager loading — current limitation

`with($paths)` loads the full related row, not a field-narrowed subset. This still eliminates the
N+1 risk (the primary performance hazard) but is not maximally minimal payload — see
[package-extraction.md](package-extraction.md) for the upgrade path if this becomes measurable.

## Large relation option lists

See [relationships.md](relationships.md#large-relation-option-lists) — `BelongsToFilter::async()`
is fully wired: the filter panel renders a search-as-you-type picker backed by
`Table::searchBelongsTo()`. Nothing is queried until the picker is typed into, results are
capped at `Table::BELONGS_TO_MAX_RESULTS`, only the key plus declared search/display fields
are selected, and every picked id is re-verified through the same authorized options query
in `selectBelongsToOption()`.

## Pagination recommendations

- Prefer `simplePaginate()` over `paginate()` when the UI doesn't need a total page count — it
  skips the `count(*)` query entirely.
- Never call `->get()` on an unbounded table query outside pagination.

## Export recommendations (for when export is implemented)

`exportable()` is currently a config flag only (see [columns.md](columns.md)). When an exporter is
built, it must: reuse the same authorized query and authorized column set as the live table
(never a separate unscoped export path), chunk large datasets, and never hydrate the full result
set into memory at once.

## Recommended database indexes

For any real table using this engine, index:

- Every column marked `->searchable()`
- Every column/relation targeted by a `Filter`
- Every column marked `->sortable()`
- Every foreign key used by a `RelationColumn`/relation filter
- `user_table_preferences(user_id, table_key)` — already a unique index from the migration
- `table_views(user_id, table_key)` — already indexed from the migration
