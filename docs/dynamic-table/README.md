# Dynamic Table Engine

A config-driven, Livewire + Flux UI data table engine built for this application. Tables are
defined as PHP classes with a fluent column/filter API — no PowerGrid, Filament Tables,
Rappasoft, or other third-party table package is used or required.

Every feature documented here is implemented and covered by Pest tests unless explicitly marked
🔮 Planned or ❌ Unsupported. See [testing.md](testing.md) for what's verified.

## What it is

- **Core** (`app/Support/DynamicTable/Core/`) — framework-facing column/filter/state definitions.
  No dependency on any Eloquent model, `Modules/*`, or Livewire/Flux.
- **Query engine** (`app/Support/DynamicTable/Query/`) — compiles a trusted table definition +
  validated client state into a real Eloquent query. No query ever runs in a Blade view.
- **Livewire + Flux** (`app/Livewire/DynamicTable/`, `resources/views/livewire/dynamic-table/`,
  `resources/views/components/dynamic-table/`) — one Livewire component per table instance,
  rendered with Flux UI Free components.
- **Preference storage** (`app/Support/DynamicTable/PreferenceStores/`, `app/Models/`) — Eloquent
  storage for per-user column preferences and personal saved views, behind swappable contracts.

## Feature matrix

| Feature | Status |
|---|---|
| Typed columns (Text/Number/Money/Date/DateTime/Boolean/Enum/Relation/Computed) | ✅ Implemented |
| Relationship columns (BelongsTo, nested BelongsTo) | ✅ Implemented |
| Global search (multi-column, relation-aware, multi-field on relations) | ✅ Implemented |
| Search driver contract + Scout adapter | ✅ Implemented — `DatabaseSearchDriver` (default), `ScoutSearchDriver` |
| Typed filters + operators (text/number/date/boolean/enum/belongsTo) | ✅ Implemented, including UI |
| Filter authorization (`Filter::visible()`, inherits from a same-key column) | ✅ Implemented |
| Relationship filters (dotted key, BelongsToFilter) | ✅ Implemented |
| Single & additive multi-column sorting, PK tie-breaker | ✅ Implemented — click to sort, shift-click to add/remove a sort, priority badges |
| Relation sorting: BelongsTo, HasMany/BelongsToMany with `->aggregate()` | ✅ Implemented |
| Relation sorting: HasOne, multi-level paths | ❌ Unsupported |
| Standard & simple pagination, per-table configurable | ✅ Implemented — `protected function paginationMode(): string` |
| Cursor pagination | ❌ Unsupported — requesting it throws rather than silently falling back |
| Model-based default query (`protected ?string $model`) | ✅ Implemented — `query()` no longer forced on every table |
| Column visibility (authorization boundary) | ✅ Implemented, hardened — see security.md for the search-leak fix |
| User-controlled column visibility & drag-and-drop ordering | ✅ Implemented — Livewire 4 `wire:sort` + keyboard fallback |
| Persistent per-user preferences (column visibility/order/per-page) | ✅ Implemented |
| Personal saved views, full UI (create/apply/rename-by-resave/delete/default/reset) | ✅ Implemented |
| Active filter chips, applied-vs-draft indicator | ✅ Implemented |
| Responsive Flux UI rendering | ⚠️ Partial — desktop verified; mobile/RTL/dark-mode not yet verified (no browser test environment available in this session) |
| Authorization & security enforcement | ✅ Implemented, hardened across sessions (search leak, filter authorization, panel exposure) |
| Export | ✅ Implemented — CSV streaming download (`Table::export()`), respects visible/ordered columns, `Column::exportable(false)`, selection, and select-all-matching |
| Row/bulk actions | ✅ Implemented — row selection + select-all-matching + bulk delete (`Table::bulkDelete()`) |
| Summaries (count/sum/avg/min/max) | ✅ Implemented — `Column::summary()`, one aggregate query over the filtered/searched query (not selection or pagination), rendered as a footer row |
| Between/not-between two-value filter UI | ✅ Implemented — dynamic two-input control, operator-specific hiding of the value control entirely for is_empty/is_not_empty/today/yesterday/this_week/this_month |
| Async `BelongsToFilter` picker | ✅ Implemented — debounced search, min length, bounded/narrow-selected results, chips for multiple, escaped labels, previously-selected label resolution |
| Strict timezone-aware date filtering | ✅ Implemented — strict `createFromFormat()` parsing with round-trip validation, per-filter timezone, DST-correct day boundaries computed in the filter's timezone then converted to the app timezone |

This matrix is maintained honestly — an earlier version of this document overstated completeness.
See `.ai/dynamic-table/CHECKLIST.md` for the authoritative, session-by-session status.

## Installation

Nothing to install. This engine ships in this repository's `app/` tree and uses only packages
already present: Laravel 13, Livewire 4, Flux UI Free 2, Pest 5. No Composer/npm dependency was
added to build it.

## Documentation

1. [Quick start](quick-start.md)
2. [Columns](columns.md)
3. [Visibility & authorization](visibility-authorization.md)
4. [Search](search.md)
5. [Filters](filters.md)
6. [Relationships](relationships.md)
7. [Sorting & pagination](sorting-pagination.md)
8. [Preferences](preferences.md)
9. [Saved views](saved-views.md)
10. [Performance](performance.md)
11. [Security](security.md)
12. [Testing](testing.md)
13. [Extending](extending.md)
14. [Troubleshooting](troubleshooting.md)
15. [Package extraction](package-extraction.md)

## Canonical example

`App\Support\DynamicTable\Examples\DemoTable` (rendered by `App\Livewire\DynamicTable\DemoTableComponent`)
is the reference table exercised by the automated test suite. Every example in these docs matches
its real, tested API.
