# Sorting & Pagination

## Default sorting

```php
protected function defaultSort(): array
{
    return [Sort::make('created_at')->descending()];
}
```

`Sort::make($column)->ascending()` / `->descending()` build the `sorts` array applied on first
mount. Only ever set via the table definition — never client input.

## Single & multi-column sorting (✅ Implemented, including the UI)

- **Single sort**: click a header — `Table::sortBy(string $column)` replaces the entire sort list.
  Clicking the same header again flips ascending/descending.
- **Additive multi-sort**: shift-click a header — `Table::sortByAdditive(string $column)` appends
  it as the next sort priority instead of replacing the list (or flips its direction in place if
  it's already sorted). Capped at `TableState::MAX_SORTS = 5`. A priority badge (`1`, `2`, ...)
  renders next to each header once more than one column is sorted.
- **Remove one sort**: `Table::removeSort(string $column)`.
- **Reset to default**: `Table::resetSort()` restores `defaultSort()`.

`TableState::normalizeSorts()` accepts and validates any number of `{column, direction}` pairs
(deduplicated per column, capped at `MAX_SORTS`) — this is the same security boundary regardless of
whether the UI produced single or multi-sort state.

Only columns with `->sortable()` set are ever accepted:

```php
protected static function normalizeSorts(...)
{
    // ...
    if (! is_string($column) || ! $definition->column($column)?->isSortable()) {
        continue; // dropped
    }
    if (! in_array($direction, ['asc', 'desc'], true)) {
        continue; // dropped
    }
}
```

`$definition->column($column)` also enforces authorization — an unauthorized column can never be
sorted even if it happens to be `sortable()` (see [visibility-authorization.md](visibility-authorization.md)).

## Stable sorting (primary-key tie-breaker)

`TableQueryBuilder::applySort()` always appends `orderBy($model->getQualifiedKeyName(), 'asc')`
after any user-requested sorts — so two rows with an identical sort value always come back in the
same order across pages, instead of shuffling between requests.

## Column ordering (✅ Implemented, including drag-and-drop)

`columnOrder` is a separate concern from row sorting — it controls which order columns render in,
not which order rows come back in. Default order is the order returned by `columns()`.

- **Drag-and-drop**: the column manager renders toggleable columns inside a Livewire 4
  `wire:sort` list. `Table::sortColumns(string $item, int $position)` is the `wire:sort` handler —
  fires once per completed drop (SortableJS only calls `onSort` on drag end, not per pointer
  move), and delegates to `reorderColumns()`'s authorization-safe merge.
- **Keyboard-accessible fallback**: Up/Down buttons next to each toggleable column call the same
  `sortColumns()` action, for users who can't drag-and-drop.
- Fixed (`toggleable(false)`) columns are never draggable and always kept in their defined
  relative position — enforced in `reorderColumns()`, not just the UI.
- `Table::reorderColumns(array $order)` persists the result as one preference write via
  `TablePreferenceStore` — see [preferences.md](preferences.md).

## Pagination modes (✅ Implemented: standard, simple; cursor unimplemented)

| Mode | How to select it | Count query? |
|---|---|---|
| **Standard** (length-aware) | Default — no override needed | ✅ Exactly one `count(*)` |
| **Simple** | `protected function paginationMode(): string { return 'simple'; }` | ❌ None — cheaper for large tables where "total pages" isn't needed |
| **Cursor** | Requesting `'cursor'` throws `InvalidArgumentException` immediately | ❌ Unimplemented (explicitly out of scope; the spec allows deferring it) — never silently falls back to another mode |

`Table::render()` calls `paginate()` or `simplePaginate()` based on `paginationMode()`. The
pagination view (`resources/views/components/dynamic-table/pagination.blade.php`) checks
`$paginator instanceof LengthAwarePaginator` before calling `total()`/`firstItem()`/`lastItem()` —
none of those exist on a plain `Paginator` (simple mode), and calling them throws
`BadMethodCallException`. A simple-mode table shows "Page N" instead of "X–Y of Z".

Verified by `tests/Feature/DynamicTable/PerformanceRegressionTest.php` (query-count) and
`tests/Feature/DynamicTable/PaginationModeTest.php` (mode selection, simple-mode rendering never
calls `total()`, invalid mode throws rather than silently falling back).

## Per-page

`TableState::PER_PAGE_OPTIONS = [10, 25, 50, 100]`, default `25`. Any value outside this allowlist
is rejected and falls back to the default — never trusted as raw client input.

## Pagination reset behavior

`page` resets to `1` whenever:

- Search is submitted (`submitSearch()`)
- Filters are applied or cleared (`applyFilters()`, `clearFilters()`, `clearFilter()`)
- Sort changes (`sortBy()`)
- Per-page changes (`setPerPage()`)
- A saved view is applied (`applyView()`)
- Preferences are reset (`resetPreferences()`)

This prevents landing on a now-nonexistent page (e.g. page 5 of a filtered set that only has 2
pages).
