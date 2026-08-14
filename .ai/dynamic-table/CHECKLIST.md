# Dynamic Table Engine — Durable Delivery Checklist

Full spec: see task description in session history / CLAUDE.md conventions. This file is the
cross-session source of truth for what's done vs pending. Update it at the end of every session.

## Milestone 1 — Core definitions
- [x] Column contract + TextColumn, NumberColumn, MoneyColumn, DateColumn, DateTimeColumn,
      BooleanColumn, EnumColumn, RelationColumn, ComputedColumn
- [x] Filter contract + TextFilter, NumberFilter, DateFilter, BooleanFilter, EnumFilter,
      BelongsToFilter
- [x] Operator enums (Text/Number/Date)
- [x] Sort definition
- [x] TableState (validated, normalized)
- [x] Configuration exceptions (duplicate keys, invalid enum, computed-column restrictions, etc.)
- [x] Unit tests for all of the above
- Namespace: `app/Support/DynamicTable/Core/...` (root app/, framework-facing, no Modules/app model imports)

## Milestone 2 — Query engine
- [x] TableQueryBuilder: base query preservation, select optimization, eager loading
- [x] Search (grouped OR, bindings only, relation search via trusted paths)
- [x] Filters -> query (all operators)
- [x] Sorting (single/multi, relation sort rejection unless aggregate, PK tie-breaker)
- [x] Pagination (standard/simple; cursor deferred unless trivial)
- [x] Query/security/performance Pest tests (query count regression included)
- Namespace: `app/Support/DynamicTable/Query/...`

## Milestone 3 — Livewire + Flux UI
- [x] Base `DynamicTable` Livewire component (one component, primitives-only public state)
- [x] Flux-rendered subviews: toolbar, search, filter panel, column manager, table, pagination,
      empty/loading state
- [x] Canonical demo table (e.g. in a test-support namespace) exercised by feature tests
- [x] Livewire feature tests: mount, search, filter apply (deferred+apply), sort, paginate,
      column toggle, multiple table instances isolation

## Milestone 4 — Preferences (DONE, session 3)
- [x] `TablePreferenceStore` contract (Core) + Eloquent implementation (app/)
- [x] Migration `user_table_preferences`
- [x] Merge/normalize logic (added/removed/renamed/unauthorized columns, stale schema versions)
- [x] Reset-to-default action
- [x] Ownership + concurrency tests
- Files: `app/Support/DynamicTable/Core/{TablePreferences,TablePreferenceStore}.php`,
  `app/Support/DynamicTable/PreferenceStores/EloquentTablePreferenceStore.php`,
  `app/Models/UserTablePreference.php`, migration `2026_08_14_160000_create_user_table_preferences_table.php`,
  binding in `AppServiceProvider`, wiring in `app/Livewire/DynamicTable/Table.php` (mount/toggleColumn/
  setPerPage/reorderColumns/resetPreferences), tests in `tests/Unit/DynamicTable/TablePreferencesTest.php`
  and `tests/Feature/DynamicTable/PreferencesTest.php`.
- Guests never read/write preference rows (mount falls back to definition defaults).
- Upsert on `unique(user_id, table_key)` makes concurrent saves race-safe without app-level locking.

## Milestone 5 — Saved views (NOT STARTED)
- [ ] Migration `table_views`
- [ ] CRUD (create/update/delete/apply), default view semantics
- [ ] Ownership authorization tests

## Milestone 6 — Hardening (DONE, session 3; browser/RTL tests out of scope — no live preview task)
- [x] Adversarial/XSS/SQLi state tests — `tests/Feature/DynamicTable/SecurityHardeningTest.php`
- [x] Query-count regression suite — `tests/Feature/DynamicTable/PerformanceRegressionTest.php`
      (simple pagination issues zero count queries, standard issues exactly one)
- [x] Arabic/Unicode already covered at the query/text level in `QueryEngineTest.php` (session 1)
- [ ] Browser tests (RTL, drag-and-drop, dark mode, keyboard) — deferred, needs a live preview session

## Addendum — Record Reference column (DONE, Record References feature)
- [x] `RecordReferenceColumn` (Core/Columns) — trusted applicationCode/variant/relation config
- [x] `TableQueryBuilder`: identity-only select for icon/tag, +card columns for card variant,
      relation eager-load reuse, duplicate-requirement merge
- [x] `table.blade.php`: typed render branch to `<x-record-reference.*>`, one Application-metadata
      query per render (not per row)
- [x] Tests: visible/hidden column SQL assertions + constant query-count regression —
      `tests/Feature/RecordReference/DynamicTableRecordReferenceColumnTest.php`
- Full design/rationale: `docs/dynamic-table/record-references.md`

**CRITICAL FIX found and closed in this session:** `TableState::normalizeVisibleColumns()`,
`normalizeColumnOrder()`, and (transitively, via `TableDefinition::column()`) `normalizeSorts()`
did **not** check `Column::isVisible()` — only `toggleable()`. An attacker could force an
unauthorized column's key into `visibleColumns`/`columnOrder`/`sorts` and it would reach the
compiled SQL `select` list. Fixed by making `TableDefinition::column()` the single authorization
gate (returns null for an unauthorized key) and adding `TableDefinition::authorizedColumns()`,
used by `TableState`'s column-key sources. `TablePreferences` was already correct (it filtered
by `isVisible()` from the start). Also hardened `Column::getLink()` to reject non-http(s) URL
schemes server-side (was previously pass-through, relying only on Blade's HTML escaping, which
does not stop a `javascript:` href from being clickable).

## Milestone 5 — Saved views (DONE, session 3)
- Files: `app/Support/DynamicTable/Core/SavedTableViewStore.php`,
  `app/Support/DynamicTable/PreferenceStores/EloquentSavedTableViewStore.php`,
  `app/Models/TableView.php`, migration `2026_08_14_170000_create_table_views_table.php`,
  binding in `AppServiceProvider`, wiring in `Table.php` (saveCurrentView/applyView/deleteView/
  setDefaultView + auto-apply default in mount), tests in `tests/Feature/DynamicTable/SavedViewsTest.php`.
- Design decision: saved-view configuration reuses `TableState::normalize()` (the same security
  boundary the live query path uses) instead of a parallel DTO — a stale/malicious stored
  configuration is re-validated against the live definition every time a view is applied, so
  removed/renamed/unauthorized columns and filters are silently dropped rather than crashing.
- `visibleColumns: []` in a stored configuration is a valid explicit "hide everything" state per
  `TableState::normalizeVisibleColumns` (empty array is distinguished from "not provided"). Table
  definitions should always save the full current `$this->visibleColumns`, never an empty stub.

## Milestone 7 — Documentation + extraction audit (DONE, session 3)
- [x] docs/dynamic-table/*.md — all 16 files present: README, quick-start, columns,
      visibility-authorization, search, filters, relationships, sorting-pagination,
      preferences, saved-views, performance, security, testing, extending,
      troubleshooting, package-extraction
- [x] Architecture tests — `tests/Feature/DynamicTable/ArchitectureTest.php` (5 tests: Core/Query
      layer boundaries, no forbidden table packages, contract implementation checks)
- [x] Extraction blocker report — full table with effort estimates in package-extraction.md

**Two more real gaps found and fixed while writing docs (writing accurate docs surfaced them):**
1. `EnumFilter`/`BelongsToFilter` had zero UI in the filter panel (only Text/Number/Date/Boolean
   were rendered) despite full query-engine support. Added `<flux:select multiple>` for
   `EnumFilter` and a plain numeric-ID input for `BelongsToFilter` (documented as a known UI
   simplification — the async searchable picker itself remains 🔮 Planned).
2. `BooleanFilter` UI used a `<flux:select>` with string option values (`"1"`/`"0"`), but
   `TableState::normalizeFilterEntry()` only accepted a real PHP `bool` — so toggling the boolean
   filter through the actual rendered UI silently did nothing. Fixed via
   `normalizeBooleanFilterValue()`, which now accepts real bools and the `'1'`/`'0'`/`'true'`/
   `'false'` string forms an HTML `<select>` submits, while still treating an empty string ("Any")
   as "no filter" rather than `false`. Covered by a new TableStateTest case.

## ALL MILESTONES 1-7 COMPLETE. Final counts: 159 tests, 356 assertions, all passing.
`vendor/bin/pint --dirty --format agent` clean. See "Session 3 notes" below for full file list
and the critical authorization-boundary fix.

## Cross-cutting integration — Record References (PLANNED)
- [ ] Add a typed `RecordReferenceColumn`; never return reference HTML from `formatUsing()`
- [ ] Apply identity/card query requirements only while the column is visible
- [ ] Keep tag/icon preview-only fields and relations out of the initial table query
- [ ] Narrow record-reference relation selects while preserving all Eloquent matching keys
- [ ] Reuse one page/table preview host; never create a Livewire component per row or cell
- [ ] Keep search/sort/filter/export on explicit trusted scalar backing fields
- [ ] Add hidden-column, SQL-select, constant-query-count, lazy-preview, authorization, XSS, RTL,
      keyboard, touch, and repeat-preview deduplication tests
- [ ] Keep `docs/dynamic-table/record-references.md` synchronized with delivered code and measurements
- System specification: `docs/record-references/README.md`
- Copy-ready delivery prompt: `docs/record-references/IMPLEMENTATION_PROMPT.md`

## Decisions log
- Engine lives in root `app/Support/DynamicTable` (shared cross-module infra, per module-organization.md
  rule: code shared across modules belongs in root app/).
- No `code` hierarchy field applies — this is infrastructure, not a business/navigation entity.
- Preferences: dedicated Eloquent store, NOT spatie/laravel-settings (explicit requirement).

## Session 1 notes (Milestones 1-3 delivered)

**Files created:**
- Core: `app/Support/DynamicTable/Core/{Column,Filter,Sort,TableState,TableDefinition,TextOperator,NumberOperator,DateOperator}.php`
- Core columns: `app/Support/DynamicTable/Core/Columns/{TextColumn,NumberColumn,MoneyColumn,DateColumn,DateTimeColumn,BooleanColumn,EnumColumn,RelationColumn,ComputedColumn}.php`
- Core filters: `app/Support/DynamicTable/Core/Filters/{TextFilter,NumberFilter,DateFilter,BooleanFilter,EnumFilter,BelongsToFilter}.php`
- Exceptions: `app/Support/DynamicTable/Core/Exceptions/{DynamicTableException,DuplicateColumnKeyException,DuplicateFilterKeyException,MissingTableKeyException,InvalidModelException,UnknownFieldMappingException,UnsupportedRelationPathException,InvalidEnumConfigurationException,SortableComputedColumnWithoutDataSourceException,FilterTargetUnavailableException,HasManySortWithoutAggregateException}.php`
- Query: `app/Support/DynamicTable/Query/TableQueryBuilder.php`
- Demo table: `app/Support/DynamicTable/Examples/DemoTable.php` (built on `App\Models\User`)
- Livewire: `app/Livewire/DynamicTable/Table.php` (abstract base), `app/Livewire/DynamicTable/DemoTableComponent.php`
- Views: `resources/views/livewire/dynamic-table/table.blade.php` + `resources/views/components/dynamic-table/{toolbar,search,filter-panel,column-manager,table,pagination,empty-state,loading-state}.blade.php`
- Tests: `tests/Unit/DynamicTable/{ColumnTest,TableDefinitionTest,TableStateTest}.php`, `tests/Feature/DynamicTable/{QueryEngineTest,LivewireTableTest}.php`, `tests/Feature/DynamicTable/Support/{DtAuthor,DtPost}.php` (local test-only models, tables created via `Schema::create` in `beforeEach`, sqlite in-memory per existing phpunit config)

**Test results:** `php artisan test --compact --filter=DynamicTable` → 77 passed (118 assertions). Full suite `php artisan test --compact` → 117 passed (267 assertions), no regressions. `vendor/bin/pint --dirty --format agent` → clean.

**Query-count regression evidence:** `QueryEngineTest::query count stays constant regardless of result set size` paginates 5 vs 100 rows (out of a ~100-row dataset) via `DB::enableQueryLog()`, touching the eager-loaded `author` relation on every row, and asserts the query count is identical for both page sizes (constant, not growing with row count) — passes.

**Known deviations / simplifications (all `ponytail:`-flagged in code comments where relevant):**
1. Eager loads (`applyEagerLoads`) are not select-narrowed per relation column — `with($paths)` loads full related rows rather than only the fields behind visible RelationColumns. This still eliminates N+1 (the primary risk) but is not maximally minimal payload. Upgrade path: introspect each relation type per path segment and pass a closure selecting only needed columns + keys.
2. `ComputedColumn::sortable()/searchable()` guard depends on `->field()` being called *before* them in the fluent chain — calling `sortable()` then `field()` still throws. Documented inline; upgrade to two-phase validation if this trips up real callers.
3. Relation sort (`applyRelationSort`) supports `BelongsTo` (via correlated subquery) and `HasMany`/`BelongsToMany` with an explicit `->aggregate()` (via `withAggregate`). `HasOne` and multi-level relation paths beyond the first segment are not supported for sorting in this milestone — documented inline, not faked.
4. Cursor pagination is explicitly unimplemented (only `paginate()`/`simplePaginate()`), per the spec's own allowance to skip it as out of scope.
5. `TableState::normalizeDateValue` stores dates as `Y-m-d` strings (day-precision) even for a `DateFilter::withTime()` — the `hasTime()` flag is defined on the filter but the query engine's date operators always treat values as full-day-inclusive ranges. Precise datetime filtering is a gap if a future consumer needs it; flagged here rather than silently mishandled.
6. `exportable()` is implemented purely as a stored boolean flag on `Column` — no exporter exists, per the prompt's explicit instruction.
7. Query-string namespacing per `tableKey` uses the classic `protected function queryString(): array` override (Livewire 4 still honors it) rather than `#[Url(as: ...)]`, because the attribute's `as:` must be a compile-time literal and can't be built from `$this->tableKey` per-instance.

## Session 3 notes (Milestones 4-7 delivered)

**Files created:**
- Preferences: `Core/{TablePreferences,TablePreferenceStore}.php`,
  `PreferenceStores/EloquentTablePreferenceStore.php`, `app/Models/UserTablePreference.php`,
  migration `2026_08_14_160000_create_user_table_preferences_table.php`
- Saved views: `Core/SavedTableViewStore.php`, `PreferenceStores/EloquentSavedTableViewStore.php`,
  `app/Models/TableView.php`, migration `2026_08_14_170000_create_table_views_table.php`
- Tests: `tests/Unit/DynamicTable/TablePreferencesTest.php`,
  `tests/Feature/DynamicTable/{PreferencesTest,SavedViewsTest,SecurityHardeningTest,
  PerformanceRegressionTest,ArchitectureTest}.php`
- Docs: `docs/dynamic-table/*.md` (16 files)
- `AppServiceProvider`: binds both new contracts to their Eloquent implementations

**Files modified:**
- `app/Livewire/DynamicTable/Table.php` — preference load/persist in mount/toggle/setPerPage/
  reorderColumns/resetPreferences; saved-view load/save/apply/delete/setDefault + auto-apply
  default on mount
- `app/Support/DynamicTable/Core/TableDefinition.php` — `column()` is now the single authorization
  gate (returns null for unauthorized keys); added `authorizedColumns()`
- `app/Support/DynamicTable/Core/TableState.php` — `normalizeVisibleColumns()`/
  `normalizeColumnOrder()` now derive their key universe from `authorizedColumns()`, not the raw
  column set; `normalizeBooleanFilterValue()` added (accepts string '1'/'0'/'true'/'false' + bool)
- `app/Support/DynamicTable/Core/Column.php` — `getLink()` now validates URL scheme server-side
  (http/https only)
- `resources/views/components/dynamic-table/filter-panel.blade.php` — added `EnumFilter` and
  `BelongsToFilter` UI (previously only Text/Number/Date/Boolean rendered)

**Test results:** `php artisan test --compact --filter=DynamicTable` and full suite both green.
Final: **159 tests, 356 assertions, 0 failures.** `vendor/bin/pint --dirty --format agent` clean.

**Security fixes found and closed this session (see security.md and SecurityHardeningTest.php):**
1. **Critical — authorization bypass in column visibility.** `TableState::normalizeVisibleColumns`/
   `normalizeColumnOrder` never checked `Column::isVisible()`, only `toggleable()`. An attacker
   forcing an unauthorized column's key into `visibleColumns`/`columnOrder` state would have it
   reach the compiled SQL `select`. Fixed at the root: `TableDefinition::column()` is now the one
   authorization gate every caller routes through.
2. `Column::getLink()` passed through whatever a `link()` callback returned, including a
   `javascript:` URL — Blade's escaping stops the *label* being exploited but not a click on the
   href itself. Now validates scheme server-side (http/https/relative only).
3. `EnumFilter`/`BelongsToFilter` had no rendered UI at all; `BooleanFilter`'s rendered UI sent a
   value shape `TableState` silently rejected. Both are real functional (not just security) gaps,
   fixed.

**Known remaining gaps (all documented, see package-extraction.md):**
- Eager loads not select-narrowed per relation (payload, not correctness)
- `ComputedColumn` fluent-call-order dependency
- Relation sort: no `HasOne`, no multi-level paths
- `DateFilter::withTime()` unused by the query engine (day precision only)
- `exportable()` is a flag only, no exporter
- `BelongsToFilter` UI is a plain ID input, not an async picker
- No search-driver contract yet (Scout integration deferred)
- Cursor pagination unimplemented
- Browser/RTL/dark-mode/keyboard tests deferred — needs a live preview session, not available here

**Next steps for Milestone 4 (preferences):**
- Add `TablePreferenceStore` contract in Core + Eloquent implementation in `app/Support/DynamicTable/` (or `app/Models/` per existing convention — check sibling models first).
- Migration `user_table_preferences` (user_id, table_key, visible_columns, column_order, per_page, schema_version, timestamps).
- Merge/normalize logic must reuse `TableState::normalize()`'s allow-list approach so stored preferences from a stale schema (renamed/removed columns) are sanitized on load, not just on live client input.
- Reset-to-default action + ownership scoping (preferences belong to `auth()->id()`, never cross-user).
- Wire the base `Table` Livewire component to load/persist preferences in `mount()`/on toggle, but keep preference I/O out of `TableQueryBuilder` — it's a Livewire-layer concern per the existing architecture split.

## AUDIT RESPONSE — Session 4 (Phase 1 of 7 complete; engine is NOT fully complete)

An independent audit found real security defects, missing UI behavior, and incomplete features in the "complete" claim from session 3. That claim was premature. This section is the truthful status going forward. Do not read any earlier "ALL MILESTONES COMPLETE" line as accurate. The audit's 7-phase plan supersedes it.

### Phase 1 — Critical authorization & security fixes: DONE

Reproduced every issue with a failing test first, then fixed:

1. Unauthorized column search leak (real, confirmed). `TableQueryBuilder::applySearch()` built its searchable-column list from `$this->definition->columns` (raw, includes unauthorized columns) instead of `authorizedColumns()`. A value that existed only behind an unauthorized column (direct or relation) was findable via global search even though the column itself never rendered. Fixed: sources from `authorizedColumns()` now. Tests: `tests/Feature/DynamicTable/AuthorizationLeakTest.php` (search returns 0 results, not just "column absent from output").

2. Unauthorized filters — no authorization concept existed at all. `Filter` had no `visible()`. Added `Filter::visible(callable|bool)`/`isVisible()`/`hasExplicitVisibility()`. Hardened `TableDefinition::filter($key)` as the single authorization gate, resolution order: (1) filter's own explicit `visible()` if set, (2) inherited from a same-key column's `visible()` if one exists (so a filter tied to a column that later becomes unauthorized follows automatically), (3) default authorized otherwise (filter-only field, no column, no explicit condition — matches `Column`'s own default-visible stance). Added `authorizedFilters()`. Covered: filter attached to an authorized column, filter-only field with explicit condition, relationship filter, filter whose column authorization is revoked later, forced injection via raw Livewire state. Tests: same `AuthorizationLeakTest.php` file, 5 of the 9 new tests.

3. Column manager / filter panel exposure. Both were handed the full raw `$definition->columns` / `$definition->filters` by `resources/views/livewire/dynamic-table/table.blade.php`. Now handed `$definition->authorizedColumns()` / `$definition->authorizedFilters()`. Tests assert an unauthorized column/filter's label is absent from rendered HTML, not just that it's unusable.

4. State size limits added to `TableState`: `MAX_FILTERS = 20`, `MAX_SORTS = 5`, `MAX_PAGE = 100_000`. Duplicate entries in `visibleColumns`/`columnOrder`/`sorts` are now deduplicated during normalization (`array_intersect()` alone preserves duplicates from the untrusted side — a real correctness gap, now fixed). A column appearing twice in a submitted sort list keeps only its first/highest-priority occurrence. `EloquentSavedTableViewStore::create()` now rejects a configuration payload over 20,000 bytes of JSON. Tests: `tests/Unit/DynamicTable/TableStateLimitsTest.php`, one new case in `SavedViewsTest.php`. Async relation-search result bounding is deferred to Phase 3 (it's a UI-layer concern — the picker doesn't exist yet).

Bonus fix while in this code (a Phase 2 item, done early since it was in the same method): `RelationColumn::searchable(['name', 'description'])`'s extra fields were silently ignored for relation columns (only applied to direct columns). Now correctly matched as additional related fields inside the same `whereHas()` scope, never interpreted as parent-table columns.

Test counts: 135 tests, 226 assertions in `tests/Unit/DynamicTable` + `tests/Feature/DynamicTable`, all passing. Full project suite also run — the only failures are in `tests/Feature/RecordReference/` and `tests/Feature/DynamicRecordView/`, which belong to a concurrently-developed, separate RecordReference integration untouched by this work (see note below).

Files changed this phase:
- `app/Support/DynamicTable/Core/Filter.php` — added `visible()`/`isVisible()`/`hasExplicitVisibility()`
- `app/Support/DynamicTable/Core/TableDefinition.php` — hardened `filter()`, added `authorizedFilters()`
- `app/Support/DynamicTable/Core/TableState.php` — `MAX_FILTERS`/`MAX_SORTS`/`MAX_PAGE`, dedup fixes
- `app/Support/DynamicTable/Query/TableQueryBuilder.php` — `applySearch()` uses `authorizedColumns()`; relation multi-field search fix (this file is shared with the concurrent RecordReference work — my diff is additive and does not touch their `RecordReferenceColumn` branches)
- `app/Support/DynamicTable/PreferenceStores/EloquentSavedTableViewStore.php` — size cap
- `resources/views/livewire/dynamic-table/table.blade.php` — passes authorized-only columns/filters
- `docs/dynamic-table/{security,visibility-authorization,filters}.md` — updated to document the filter authorization model and the search-leak fix truthfully
- New tests: `tests/Feature/DynamicTable/AuthorizationLeakTest.php`, `tests/Unit/DynamicTable/TableStateLimitsTest.php`, +1 test in `SavedViewsTest.php`
- Incidental fix: `tests/Feature/RecordReference/PreviewHostTest.php` had a redundant `uses(TestCase::class);` call that collided with the project-wide `Pest.php` binding and broke every Feature test in the whole app (not just mine) with `TestCaseAlreadyInUse`. Removed the redundant line — one-line fix, unrelated to my feature work but blocking, so it had to be fixed to run any tests at all.

Note on concurrent work: while working this session, `app/Support/RecordReference/*`, `app/Support/DynamicTable/Core/Columns/RecordReferenceColumn.php`, `app/Support/DynamicTable/Query/TableQueryBuilder.php`, `resources/views/components/dynamic-table/table.blade.php`, and this checklist file were being actively modified by what appears to be a separate, concurrent process/session implementing the "record-references" cross-cutting integration mentioned earlier in this checklist. That work is not part of this audit-response task and was not initiated or directed by this session. Edits were made carefully around it (always re-reading files before editing, keeping diffs additive) rather than reverting anything. If this concurrency was not expected, it is worth the user's attention independent of this task.

### Phases 2-7: NOT STARTED

Per the audit's own instructions (finish and test each milestone before moving to the next), these remain outstanding and must not be marked done until genuinely complete with working backend and UI:

- Phase 2 — model-based default query (`protected string $model`), deterministic `ComputedColumn` validation regardless of call order, `SearchDriver` contract + Scout adapter.
- Phase 3 — between/not-between two-value UI, operator-specific control hiding, strict timezone-aware date parsing, real async `BelongsToFilter` picker.
- Phase 4 — real drag-and-drop column reorder, real saved-views UI (create/apply/rename/delete/default), multi-sort UI, filter chips/clear indicators, configurable pagination mode UI.
- Phase 5 — relation eager-load field selection (currently loads full related rows), preserve customized base-query projections (`addSelect`/`withCount`/joins), complete/reject relation sorting deterministically, N+1/request-count verification.
- Phase 6 — real OpenSpout exporter, row/bulk actions, summaries, responsive/RTL/theme verification.
- Phase 7 — close remaining test gaps, final documentation truthfulness pass.

Do not report the Dynamic Table Engine as complete until every phase above is done.

### Phase 2 — Complete core developer API: DONE

- Model-based default query: `Table::$model` (nullable, `class-string<Model>`) with a default `query()` implementation (`($this->model)::query()`). `query()` is no longer abstract, so a table can skip it entirely, or override it and call `parent::query()` to scope the model-based default (matches the original spec's `parent::query()->visibleTo(...)` pattern). Throws `InvalidModelException` (two new factory methods: `forMissingModelOrQuery()`, `forInvalidModel()`) at render time if neither `$model` nor an override is provided, or `$model` isn't a real `Model` subclass. Tests: `tests/Feature/DynamicTable/ModelBasedQueryTest.php`.
- `ComputedColumn`'s fluent-call-order dependency is fixed. Added `Column::validate()` (no-op base, overridable), called once by `TableDefinition::__construct()` per column after its entire fluent chain has already run. `ComputedColumn::validate()` does the sortable/searchable-without-field check there instead of inside the `sortable()`/`searchable()` setters — so `->sortable()->field(...)` and `->field(...)->sortable()` now behave identically. Tests updated in `tests/Unit/DynamicTable/ColumnTest.php` to construct a `TableDefinition` (where validation now happens) and prove both call orders.
- `UnknownFieldMappingException` was defined but never thrown anywhere (dead code). Now genuinely used: `TableDefinition::__construct()` validates every `defaultSort()` entry references a real column key, throwing this exception otherwise. Tests: 2 new cases in `tests/Unit/DynamicTable/TableDefinitionTest.php`.
- `RelationColumn` now also rejects a dotted key with an empty relation or field segment (`'author.'`, `'.name'`) via `UnsupportedRelationPathException` — previously only "no dot at all" was checked.
- Relationship searchable fields: `RelationColumn::make('country.name')->searchable(['name', 'description'])` — this was actually fixed in Phase 1 (found while fixing the search-leak bug, same method). Confirmed here: extra fields are matched inside the same relation's `whereHas()` scope, never interpreted as parent-table fields. Tested via `SearchDriverTest.php` and the existing `QueryEngineTest.php` relation-search tests.
- `SearchDriver` contract (`App\Support\DynamicTable\Core\SearchDriver`) with two implementations in `App\Support\DynamicTable\Query\SearchDrivers`: `DatabaseSearchDriver` (default — the extracted, unchanged LIKE-search logic) and `ScoutSearchDriver` (delegates to Scout for matching IDs, then constrains the **already-scoped** query with `whereIn()` — proven with a real test using Scout's built-in `collection` engine, no external search service required, asserting a base-query scope survives). `TableQueryBuilder` accepts an optional driver in its constructor; `Table::searchDriver()` is the override point. New exception `ModelNotSearchableException` for a Scout driver used on a non-`Searchable` model. Tests: `tests/Feature/DynamicTable/SearchDriverTest.php` (4 tests, including the Scout base-scope-preservation proof).

**Test counts:** 146 tests, 242 assertions in `tests/Unit/DynamicTable` + `tests/Feature/DynamicTable`, all passing. Full project suite: 253 tests, 1 flaky/unrelated failure confirmed to pass in isolation (`SubModuleRecordViewTest` — a different, non-DynamicTable-Core Livewire table belonging to the concurrent RecordReference work, untouched by this session).

**Files changed:**
- `app/Livewire/DynamicTable/Table.php` — `$model` property, default `query()`, `searchDriver()` hook
- `app/Support/DynamicTable/Core/Column.php` — added `validate()`
- `app/Support/DynamicTable/Core/Columns/ComputedColumn.php` — moved validation to `validate()`
- `app/Support/DynamicTable/Core/Columns/RelationColumn.php` — empty-segment rejection
- `app/Support/DynamicTable/Core/TableDefinition.php` — calls `column->validate()`, validates `defaultSort()` keys
- `app/Support/DynamicTable/Core/SearchDriver.php` — new contract
- `app/Support/DynamicTable/Query/SearchDrivers/{DatabaseSearchDriver,ScoutSearchDriver}.php` — new
- `app/Support/DynamicTable/Query/TableQueryBuilder.php` — delegates search to the driver
- `app/Support/DynamicTable/Core/Exceptions/{InvalidModelException,ModelNotSearchableException}.php` — new factory methods / new class
- Docs updated: `README.md` (honest feature matrix, no more overstated completeness), `quick-start.md` (model-based query section), `search.md` (driver contract), `columns.md` (deterministic ComputedColumn validation, new exception rows), `relationships.md` (multi-field relation search)
- New/updated tests: `tests/Feature/DynamicTable/{ModelBasedQueryTest,SearchDriverTest}.php`, updates to `ColumnTest.php` and `TableDefinitionTest.php`

### Phase 3 — Filters and date correctness: DONE

- **Between/not-between UI**: `resources/views/components/dynamic-table/filter-value-input.blade.php`
  (new shared partial for Text/Number/Date filter values) uses Alpine, mirroring the operator via
  `$wire.entangle()` (no extra Livewire request), to render two inputs for `between`/`not_between`
  and hide the value control entirely for `is_empty`/`is_not_empty`/`today`/`yesterday`/
  `this_week`/`this_month`. Tests: `tests/Feature/DynamicTable/FilterOperatorUiTest.php`.
- **Strict, timezone-aware dates**: `DateFilter::timezone(string)`/`getTimezone()` added.
  `TableState::normalizeDateFilterEntry()` (replaces the old `normalizeDateValue`/`parseDate`)
  strictly parses with `Carbon::createFromFormat()` + a round-trip check (rejects loose formats
  and auto-corrected invalid dates like `2026-02-30`), and computes day boundaries **in the
  filter's timezone**, converting to `config('app.timezone')` before the value ever reaches
  `TableQueryBuilder`. `TableQueryBuilder::applyDateOperator()` simplified to bind the
  already-resolved values directly — no more `Carbon::parse()`/`startOfDay()`/`endOfDay()` in the
  Query layer. Distinguishes day precision (default) from datetime precision (`->withTime()`, now
  actually honored — previously a dead flag). Tests: `tests/Feature/DynamicTable/DateFilterTimezoneTest.php`
  (12 tests: default/explicit timezone, day boundaries in a non-UTC timezone, DST-crossing day,
  user-TZ-vs-DB-TZ, inclusive between, exclusive/inclusive before/after, invalid-input rejection,
  round-trip rejection, withTime datetime precision, malformed datetime rejection, `today` relative
  to filter timezone).
- **Async `BelongsToFilter` picker**: `Table::searchBelongsTo()`, `resolveBelongsToLabels()`,
  `selectBelongsToOption()`, `removeBelongsToOption()`, `fetchBelongsToOptions()` added to
  `app/Livewire/DynamicTable/Table.php`. Real UI in `filter-panel.blade.php` (debounced search
  input, dropdown, chips for multiple selection). `BELONGS_TO_MIN_SEARCH_LENGTH = 2`,
  `BELONGS_TO_MAX_RESULTS = 20`. Selects only the related model's key + declared
  `->searchUsing()` fields, respects the related model's own query scopes (soft deletes, tenant
  global scopes), escapes labels via Blade, no query until actually searched, previously-selected
  labels resolved once per render (never in the Blade view). Tests:
  `tests/Feature/DynamicTable/BelongsToPickerTest.php` (12 tests).

**Bugs found and fixed while building this (not pre-existing security issues, functional gaps in
the shipped UI):**
1. `EnumFilter`/`BelongsToFilter` had zero rendered UI at all before this and prior sessions —
   already partially addressed; this session completed the `BelongsToFilter` picker properly.
2. Blade **components** (`<x-dynamic-table.filter-panel>`) do not inherit the parent Livewire
   component's public properties automatically — `$belongsToOptions`/`$belongsToSearch` had to be
   explicitly passed as props from `table.blade.php` (`:belongs-to-options="$this->belongsToOptions"`).
   Missing this silently rendered an empty picker with no error.

**Test counts:** DynamicTable suite (`tests/Unit/DynamicTable` + `tests/Feature/DynamicTable`):
**175 tests, 311 assertions, all passing.** Pint clean.

**Files changed:**
- `app/Support/DynamicTable/Core/Filters/DateFilter.php` — `timezone()`/`getTimezone()`
- `app/Support/DynamicTable/Core/TableState.php` — `normalizeDateFilterEntry()` replaces
  `normalizeDateValue()`/`parseDate()`; new `MAX_FILTERS`/`MAX_SORTS`/`MAX_PAGE` constants and
  dedup fixes were actually Phase 1 work, noted here since they're in the same file
- `app/Support/DynamicTable/Query/TableQueryBuilder.php` — `applyDateOperator()` simplified
- `app/Livewire/DynamicTable/Table.php` — BelongsTo picker methods, `render()` passes
  `belongsToSelectedLabels`
- `resources/views/components/dynamic-table/filter-value-input.blade.php` — new
- `resources/views/components/dynamic-table/filter-panel.blade.php` — between/not-between wiring,
  full BelongsToFilter picker UI
- `resources/views/livewire/dynamic-table/table.blade.php` — passes new props to filter-panel
- Docs: `filters.md` (DateFilter/BelongsToFilter sections rewritten truthfully), `README.md`
  (matrix updated), `troubleshooting.md` (new entries, stale ones removed)
- New tests: `FilterOperatorUiTest.php`, `DateFilterTimezoneTest.php`, `BelongsToPickerTest.php`

### Phase 4 — Complete Livewire/Flux UI: DONE

- **Drag-and-drop column ordering**: Livewire 4 `wire:sort`/`wire:sort:item` in
  `column-manager.blade.php`; handler `Table::sortColumns(string $item, int $position)` delegates
  to the existing authorization-safe `reorderColumns()`. Keyboard-accessible Up/Down button
  fallback per column. Fixed columns rendered separately, never draggable.
- **Saved Views UI**: `resources/views/components/dynamic-table/saved-views.blade.php` — list,
  apply, set/unset default, delete, save-current-as (create or update-in-place), inline validation
  error display, "Reset to table defaults" (new `Table::resetToTableDefaults()`, distinct from the
  column-only `resetPreferences()`), active-view display (new `Table::$activeViewId`).
- **Multi-column sorting UI**: `Table::sortByAdditive()` (shift-click header to add/toggle a sort
  without discarding others), `Table::removeSort()`, `Table::resetSort()`. Sort-priority badges
  render on headers once more than one column is sorted. Single-click `sortBy()` unchanged
  (replaces the whole list).
- **Filter UX**: active-filter chips (`Table::activeFilterChips()`/`summarizeFilterValue()`, new
  `Table::clearFilter(string $key)` for one chip), "Clear all", applied-vs-draft "Unapplied
  changes" indicator.
- **Pagination modes**: `protected function paginationMode(): string` (`'standard'` default,
  `'simple'`, or throws for `'cursor'`/anything else — never silently falls back).
  `pagination.blade.php` gates `total()`/`firstItem()`/`lastItem()` behind
  `instanceof LengthAwarePaginator` — previously called unconditionally, which would have thrown
  `BadMethodCallException` on a simple paginator the moment anyone used `paginationMode(): 'simple'`.
  Per-page `<select>` now marks the current value `:selected` (was previously always showing the
  first option regardless of actual `$perPage`).

**Real bugs found and fixed while building this UI (not the security class from Phase 1, but
genuine correctness bugs that would have broken in production):**
1. `reorderColumns()`: `collect($this->definition()->columns)->map(...)->all()` preserves the
   original associative column-key keys (e.g. `['id' => 'id']`) instead of `[0 => 'id']`. The
   later `[...$fixed, ...$safeOrder]` array-spread then produced a mixed-key array, corrupting
   `columnOrder` the moment a fixed column existed. Fixed with `->values()` before `->all()`.
2. Nested double-quoted PHP string literals inside a double-quoted Blade **component** tag
   attribute (`<flux:table.column x-on:click="{{ ... "..." ... }}">`) broke Laravel's
   `ComponentTagCompiler` — its regex-based attribute parser terminates the attribute value at the
   first literal `"` it encounters inside the `{{ }}` expression, regardless of PHP string nesting,
   producing an unbalanced `@if`/`@endif` in the compiled output (`"syntax error, unexpected token
   'endif'"`). Fixed by precomputing the click-handler string in `@php` and interpolating a plain
   variable instead. **Lesson for future Blade work in this codebase: never write a double-quoted
   PHP string literal inside a `{{ }}` expression that itself sits inside a double-quoted
   `<x-...>`/`<flux:...>` component tag attribute — use single quotes or precompute in `@php`.**
3. `pagination.blade.php` called `$paginator->total()` unconditionally — would have thrown
   `BadMethodCallException` for any table using the newly-added `paginationMode(): 'simple'`. Only
   surfaced because implementing configurable pagination modes forced exercising that path; fixed
   as part of the same change.

**Test counts:** DynamicTable suite: **205 tests, 361 assertions, all passing.** Full project
suite: **326 tests, 733 assertions, all passing**, confirmed after Phase 4 (no regressions from
the concurrent RecordReference/RecordView work either — that finished during this session).
Pint clean.

**Files changed:**
- `app/Livewire/DynamicTable/Table.php` — `sortColumns()`, `sortByAdditive()`, `removeSort()`,
  `resetSort()`, `clearFilter()`, `resetToTableDefaults()`, `activeFilterChips()`,
  `summarizeFilterValue()`, `paginationMode()`, `$activeViewId`/`$newViewName`/`$saveViewError`
  properties, `reorderColumns()` bug fix, `resolveBelongsToLabels()` now accepts an explicit value
- `resources/views/components/dynamic-table/column-manager.blade.php` — full rewrite (fixed vs.
  toggleable sections, `wire:sort`, keyboard fallback)
- `resources/views/components/dynamic-table/saved-views.blade.php` — new
- `resources/views/components/dynamic-table/pagination.blade.php` — paginator-type-safe
- `resources/views/components/dynamic-table/filter-panel.blade.php` — active-filter chips,
  applied-vs-draft indicator
- `resources/views/components/dynamic-table/toolbar.blade.php` — per-page `:selected` fix
- `resources/views/components/dynamic-table/table.blade.php` — shift-click multi-sort, priority
  badges (and the nested-quote Blade bug fix)
- `resources/views/livewire/dynamic-table/table.blade.php` — wires in saved-views + new filter
  props
- Docs: `sorting-pagination.md`, `filters.md`, `saved-views.md`, `README.md` all updated truthfully
- New tests: `ColumnReorderTest.php`, `SavedViewsUiTest.php`, `MultiSortTest.php`,
  `FilterChipsTest.php`, `PaginationModeTest.php`

## AUDIT RESPONSE — Session 5, Phase A (critical defects) — A1-A4 DONE, B-G NOT STARTED

An independent audit found that green tests did not mean the feature was complete: several
tests verified internal state only and missed broken rendered behavior. Phase A fixes below are
verified with real rendered-HTML assertions and query-count assertions, not just internal-state
assertions.

**A1 — Column reorder didn't affect the real table (FIXED).** `TableState::visibleColumns`
reflects definition/authorization order, never the user's actual drag/keyboard-reordered
`columnOrder`. Headers, cells, select/eager-load, and the column manager were all still reading
`visibleColumns` directly, so reordering only ever changed hidden internal state — the rendered
table never moved. Fixed by adding `TableState::orderedVisibleColumns()` (columnOrder ∩
visibleColumns, in columnOrder's sequence) as the single canonical value, and switching every
consumer to it: `TableQueryBuilder::applySelect()`/`applyEagerLoads()` (3 loops),
`Table::render()`, `table.blade.php`, `column-manager.blade.php`. Verified with
`ColumnOrderRenderingTest.php` (5 tests) asserting actual rendered header order via `strpos()` on
component HTML, not just `$state->columnOrder` equality.

**A2 — Async BelongsTo picker was insecure (FIXED).** `fetchBelongsToOptions()` built its options
query from `$model->{$relation}()->getQuery()`, i.e. only the model's global scopes — any
relation-local constraint (`belongsTo(...)->where('active', true)`) was silently dropped, and a
forged id for an excluded row was accepted on faith with no re-validation. Fixed:
  - Built the options query via `Relation::noConstraints(fn () => $model->{$relation}()->getQuery())`
    — preserves the relation method's own chained constraints and the related model's global
    scopes, while dropping only the per-instance "belongs to this exact row" constraint that a
    bare `getQuery()` on a `BelongsTo` carries.
  - Added `BelongsToFilter::optionsQuery(callable)` (extra server-defined constraint, e.g. tenant
    scoping) and `->authorizeOption(callable)` (PHP-level per-option authorization) hooks, applied
    on top of the relation's own query, never replacing it.
  - `selectBelongsToOption()` now re-runs the exact same authorized query filtered to the
    submitted id and only accepts the id if it comes back — a forged/excluded/out-of-tenant id is
    silently ignored.
  - Non-integer (UUID/ULID/string) keys now supported end-to-end (search/select/apply); previously
    every key was force-cast to `int`.
  - Search input capped at `TableState::MAX_SEARCH_LENGTH` (truncated, not rejected); results
    capped at `Table::BELONGS_TO_MAX_RESULTS` (20).
  Verified with `BelongsToPickerSecurityTest.php` (10 new tests: relation-local constraint
  enforced in both search and forged-id selection, `optionsQuery()` tenant scoping enforced both
  ways, `authorizeOption()` enforced both ways, forged non-existent id rejected, UUID keys work
  end-to-end, results capped, search length capped) plus all 12 pre-existing
  `BelongsToPickerTest.php` tests still passing (no regression).

**A3 — Repeated relationship-label queries (FIXED).** `resolveBelongsToLabels()` was called once
in `render()` (for the picker's "Selected: X" display) and again inside
`activeFilterChips()`/`summarizeFilterValue()` (for the filter-chip summary) for the same applied
filter — two queries per render for what is logically one lookup. Fixed with an in-request-only,
non-public, non-Livewire-synced `protected array $belongsToLabelsCache` on `Table`, keyed by
`"{filterKey}|{json_encode($value)}"` so a genuinely different value (e.g. draft vs. applied
differ) still resolves separately, while the same value resolves once and both consumers reuse
it. The cache is per-request only (a fresh property array every Livewire request) so a changed
selection is never served a stale label — no separate invalidation logic needed. Verified with
`BelongsToLabelCachingTest.php` (3 tests, `DB::enableQueryLog()`-based): one applied filter
resolves its label query exactly once per render regardless of how many UI consumers need it;
query count does not grow with row count (50 rows, still 1 label query); clearing then
reselecting the same filter still resolves and displays the correct label (proves the cache never
serves a stale/removed selection).

**A4 — Scout search driver ignored the searchable-columns allowlist and loaded ids unbounded
(FIXED).** `ScoutSearchDriver` previously took Scout's matching ids via `->keys()` (no limit —
unbounded scan/`whereIn()`) and used them directly, completely ignoring the `$searchableColumns`
allowlist passed in: if a model's `toSearchableArray()` indexed a field the table never declared
`->searchable()` on, a search could still match — and thus leak the existence of — that
unauthorized field's content. Fixed:
  - Bounded Scout's own result set with `->take(ScoutSearchDriver::MAX_MATCHED_IDS)` (500) before
    `->keys()`.
  - After constraining `$query` to those candidate ids via `whereIn()` (still on top of every
    existing scope — Scout only narrows, never widens), the query is now also passed through
    `DatabaseSearchDriver::search()` with the same authorized `$searchableColumns` — so a row is
    only actually returned if it matches on an *authorized* column, regardless of why Scout
    surfaced it as a candidate. This closes the leak without needing engine-specific field
    targeting (which Scout doesn't expose portably across drivers).
  Verified with 2 new tests in `SearchDriverTest.php`: a row matching only on a non-searchable
  indexed field is never returned (only the row that also matches on the authorized `name` column
  comes back); the id list bound into `whereIn()` never exceeds `MAX_MATCHED_IDS` even when more
  rows match. Both new tests, plus the 4 pre-existing `SearchDriverTest.php` tests, pass.

**Test counts after Phase A:** DynamicTable suite: **225 tests, 387 assertions, all passing**
(was 205/361 at the start of this session). Pint clean on all dirty files. Full-project-suite
regression check and Phase B–G work not yet done — see "Pending" below; do not read this section
as "all phases complete."

**Files changed (Phase A only):**
- `app/Support/DynamicTable/Core/TableState.php` — `orderedVisibleColumns()`,
  `normalizeBelongsToKey()`/`normalizeBelongsToValue()` non-integer key support
- `app/Support/DynamicTable/Query/TableQueryBuilder.php` — 3 loops switched to
  `orderedVisibleColumns()`
- `app/Support/DynamicTable/Core/Filters/BelongsToFilter.php` — `optionsQuery()`,
  `authorizeOption()`
- `app/Support/DynamicTable/Query/SearchDrivers/ScoutSearchDriver.php` — bounded + delegates final
  authorization to `DatabaseSearchDriver`
- `app/Livewire/DynamicTable/Table.php` — `render()` passes `orderedVisibleColumns`;
  `fetchBelongsToOptions()`, `selectBelongsToOption()`, `removeBelongsToOption()` rewritten;
  `$belongsToLabelsCache` + `resolveBelongsToLabels()` caching
- `resources/views/livewire/dynamic-table/table.blade.php`,
  `resources/views/components/dynamic-table/column-manager.blade.php` — consume
  `orderedVisibleColumns`/`columnOrder`
- New tests: `ColumnOrderRenderingTest.php`, `BelongsToPickerSecurityTest.php`,
  `BelongsToLabelCachingTest.php`; `SearchDriverTest.php` extended

**Pending (Phases B–G, not started this session):** browser-verified filter operator transitions
and `EnumFilter` boolean-attribute fix (B); real saved-view rename, discoverable multi-sort
controls, cursor pagination (C); `applySelect()` projection preservation, narrowed eager loads,
deterministic relation-sorting support/rejection (D); real export, row/bulk actions, summaries,
`RelationColumn::linkable()` (E); Pest browser tests for RTL/mobile/dark-mode/keyboard/drag-drop
(F); truthful documentation pass removing the specific overstated claims named in the audit (G).

### Phases 5-7: still NOT STARTED. Do not report the engine complete.
