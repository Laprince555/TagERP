# Visibility & Authorization

Column visibility is a **security boundary**, not a display preference. This page explains the
four states a column can be in and exactly what "unauthorized" excludes.

## The four states

| State | How it's set | Effect |
|---|---|---|
| **Authorized and visible** | Default | Shown, in SQL select, searchable/sortable if configured, in column manager |
| **Authorized but hidden by default** | `->hiddenByDefault()` | Exists, authorized, but starts hidden until the user toggles it on |
| **Authorized and user-hidden** | User toggles it off via the column manager | Same as above, driven by user preference instead of the definition |
| **Not authorized — completely unavailable** | `->visible(false)` or `->visible(fn () => bool)` returning `false` | Treated as if the column does not exist, anywhere |
| **Fixed column, not toggleable** | `->toggleable(false)` | Always visible; user state can never hide it |

## Filters have authorization too

`Filter::visible()` (optional) governs a filter the same way `Column::visible()` governs a column.
A filter with no explicit `visible()` call automatically inherits authorization from a column
sharing its exact key, if one exists — so `TextFilter::make('credit_limit')` is unauthorized
whenever the `credit_limit` column is. A filter-only field with no matching column needs its own
explicit `->visible(...)` to be authorization-gated; without one it defaults to authorized, same
as `Column`. See [security.md](security.md#filter-authorization) for the full resolution order and
[filters.md](filters.md) for examples.

## `visible()` is checked everywhere (✅ Implemented, hardened)

`TableDefinition::column(string $key)` — the single lookup path used by the query engine, the
Livewire component, and preference/view normalization — returns `null` for a key that exists but
whose `visible()` is currently `false`. Every caller therefore already treats it as absent. This
is enforced by `TableState::normalizeVisibleColumns()`/`normalizeColumnOrder()`, which build their
allow-list from `TableDefinition::authorizedColumns()` (only `isVisible()` columns), not the full
column set.

An unauthorized column is excluded from:

- Rendering (HTML)
- SQL `select`
- Relationship eager loading (`RelationColumn` behind an unauthorized flag is never eager-loaded)
- Global search
- Filters (sort request for an unauthorized column is silently dropped)
- Sorting
- Export (once implemented — the flag exists, see [columns.md](columns.md))
- User preferences (`TablePreferences::normalize()` re-checks authorization on every load)
- Saved views (re-normalized against the live definition, via the same `TableState::normalize()`)
- Column manager UI
- Query-string state

**No amount of client-side tampering changes this.** The engine's tests explicitly try to force
an unauthorized column's key into `visibleColumns`, `columnOrder`, and `sorts` state and assert it
never reaches the compiled SQL — see `tests/Feature/DynamicTable/SecurityHardeningTest.php`.

```php
TextColumn::make('credit_limit')
    ->label('Credit Limit')
    ->visible(fn (): bool => auth()->user()->can('view_credit_limits'));
```

If the current user can't view credit limits, this column is invisible in the truest sense — the
underlying `credit_limit` field is never even fetched from the database for that request.

## `hiddenByDefault()` vs `visible(false)`

These are unrelated:

- `hiddenByDefault()` — the column is fully authorized and fully functional; it's just not shown
  until the user opts in. It still participates in search/sort/filter if configured to.
- `visible(false)` — authorization is revoked. The column does not exist for this request, period.

## `toggleable(false)` — fixed columns

```php
TextColumn::make('id')->toggleable(false);
```

A fixed column is always in `visibleColumns` regardless of what the client sends, and
`Table::toggleColumn()` silently no-ops if called on it. In preference/column-order normalization,
fixed columns are always placed first in `columnOrder` (`TablePreferences::normalize()`).

## Testing your own authorization callbacks

Because `visible()` accepts a closure evaluated per-request (not cached), it's safe to check
`auth()->user()->can(...)` directly:

```php
BooleanColumn::make('is_flagged')->visible(fn (): bool => auth()->user()->hasRole('admin'));
```

See [testing.md](testing.md) for how to assert a column is genuinely absent as a different user.
