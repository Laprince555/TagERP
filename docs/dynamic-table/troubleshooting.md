# Troubleshooting

## `DuplicateColumnKeyException` / `DuplicateFilterKeyException`

Two columns (or two filters) in the same table definition share a key. `Column::make($key)`/
`Filter::make($key)` keys must be unique per `columns()`/`filters()` array. Rename one.

## `MissingTableKeyException`

`protected string $tableKey = '';` was never set on your `Table` subclass — it's required and
checked in `mount()`. Set a stable, explicit key independent of the class name, e.g.
`protected string $tableKey = 'finance.invoices';`.

## `UnsupportedRelationPathException`

`RelationColumn::make('name')` (no dot) — a relation column key must be `{relation}.{field}`,
e.g. `RelationColumn::make('country.name')`.

## `InvalidEnumConfigurationException`

`EnumColumn::make('status')->enum(SomeClass::class)` where `SomeClass` is not a backed enum
(`enum X: string { ... }` / `enum X: int { ... }`). Pure (non-backed) enums aren't supported.

## `SortableComputedColumnWithoutDataSourceException` / `FilterTargetUnavailableException`

A `ComputedColumn` was marked `->sortable()` or `->searchable()` before `->field()` was called.
**Call order matters** for this guard — put `->field()` first:

```php
// ❌ throws
ComputedColumn::make('x')->sortable()->field('real_field');

// ✅ works
ComputedColumn::make('x')->field('real_field')->sortable();
```

## `HasManySortWithoutAggregateException`

Attempted to sort a `RelationColumn` pointing at a `HasMany`/`BelongsToMany` relation without
declaring `->aggregate('count'|'sum'|...)`. To-many relation sorting is inherently ambiguous
without an aggregate — see [relationships.md](relationships.md#relation-sorting-limitations).

## A column I expected to see isn't rendering

Check in this order:

1. Is `visible()` returning `false` (or a closure that evaluates to `false` for the current
   user)? An unauthorized column is completely absent — see
   [visibility-authorization.md](visibility-authorization.md).
2. Is `hiddenByDefault()` set? The column exists but starts hidden until the user toggles it on
   in the column manager.
3. Did the user previously hide it? Check `user_table_preferences` for that user/table, or call
   `Table::resetPreferences()`.

## Query count seems higher than expected

- Confirm the relation column you expect to be eager-loaded is actually `->visible()` (an
  unauthorized/hidden `RelationColumn` is never eager-loaded — see
  [performance.md](performance.md)).
- Check for `RelationColumn`s at different first-segment relation paths — each unique first
  segment is one `with()` call, which is one extra query, by design (this is *not* N+1: it stays
  constant regardless of row count).
- Verify you're not calling `->get()` somewhere outside the paginated query path.

## Stale preferences / saved views after a table definition change

Both are automatically repaired on next load — `TablePreferences::normalize()` and
`TableState::normalize()` (used by `applyView()`) both drop removed/renamed/unauthorized
columns and filters rather than crashing. If something still looks wrong after a definition
change, check whether the *filter key* or *column key* itself changed (a rename is indistinguishable
from a removal+addition from the stored data's perspective — the old key is dropped, the new key
starts at its definition default, nothing is "migrated").

## Pagination lands on a blank/nonexistent page

`page` should reset to `1` automatically on search/filter/sort/per-page/saved-view changes (see
[sorting-pagination.md](sorting-pagination.md#pagination-reset-behavior)). If you've overridden
`render()` or added a new mutating action, make sure it calls `$this->resetTablePage()`.

## Livewire "cannot set property" or serialization errors

Every public property on a `Table` subclass must stay a primitive or array — never assign an
Eloquent model, `Builder`, closure, or `Column`/`Filter` instance to a public property. Build
those fresh inside `columns()`/`filters()`/`query()` (called every request) instead.

## `BooleanFilter` from the filter panel doesn't seem to apply

Fixed in this version: `TableState::normalizeBooleanFilterValue()` accepts both a real PHP `bool`
and the string form an HTML `<select>` submits (`'1'`/`'0'`/`'true'`/`'false'`), while still
correctly treating an empty string (the "Any" option) as "no filter" rather than `false`.

## A `DateFilter` value is silently dropped

Check that the submitted string matches the filter's exact expected format: `Y-m-d` normally,
`Y-m-d\TH:i` if `->withTime()` is set. Strict parsing (`Carbon::createFromFormat()` plus a
round-trip check) rejects anything else, including values `Carbon::parse()` would have loosely
accepted — see [filters.md](filters.md#datefilter). This is deliberate: silently mis-parsing an
ambiguous date is worse than dropping the filter.

## A `DateFilter`'s day boundaries look off by a few hours

Set `->timezone(...)` explicitly if the filter's implicit default (`config('app.timezone')`)
doesn't match the timezone your users actually enter dates in. "Today"/"Between"/etc. are computed
relative to the filter's declared timezone, not the database's — see
[filters.md](filters.md#timezone).

## The `BelongsToFilter` picker shows "Type to search…" and never returns results

Check the search term is at least `Table::BELONGS_TO_MIN_SEARCH_LENGTH` (2) characters, and that
`->searchUsing([...])` declares real column names on the **related** model (not the parent table).
If still empty, confirm the related model doesn't have a global scope silently excluding your test
data (the picker respects the related model's own scopes by design).
