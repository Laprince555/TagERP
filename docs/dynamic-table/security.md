# Security Model

## Trust boundary

Everything the browser can influence — Livewire public properties, query-string params, saved
view/preference JSON pulled from storage — is **untrusted input**. Only the table definition
(`columns()`, `filters()`, `query()`, `defaultSort()`, all defined in server-side PHP) is trusted.

`TableState::normalize(array $raw, TableDefinition $definition): TableState` is the **single**
point untrusted input must pass through before it can influence a query. `TableQueryBuilder` only
ever accepts a `TableState`, never a raw array — there is no code path from browser input straight
to SQL.

## Column/operator/relation/direction allow-lists

Nothing about "which column", "which operator", "which relation", or "which sort direction" is
ever taken from client input as a literal SQL identifier:

| Input | Allow-list enforcement |
|---|---|
| Column key | Must match a key in `TableDefinition::authorizedColumns()` (visible + exists) |
| Filter key | Must match a key in `TableDefinition::authorizedFilters()` (see below) |
| Filter operator | Must be one of the filter's own `TextOperator`/`NumberOperator`/`DateOperator` cases |
| Sort direction | Must be exactly `'asc'` or `'desc'` |
| Relation path | Always taken from the **column/filter definition**, never from client input — a `RelationColumn`/relation filter's path is baked into the PHP definition |
| Enum filter value | Must be one of `$enumClass::cases()` |
| Per-page | Must be one of `TableState::PER_PAGE_OPTIONS` |

## Authorization boundary: `visible()`

An unauthorized column (`visible(false)`) is excluded from every data path — see
[visibility-authorization.md](visibility-authorization.md) for the full list. This was the subject
of a real fix during hardening: `TableDefinition::column()` is now the single authorization gate
every caller (query engine, Livewire component, preference/view normalization) routes through, and
`TableState`'s column-key sources build their allow-list from `authorizedColumns()` rather than
the raw column set. See `tests/Feature/DynamicTable/SecurityHardeningTest.php`.

**Global search was found to have the same class of gap** and was fixed the same way:
`TableQueryBuilder::applySearch()` previously built its searchable-column list from
`$this->definition->columns` (the raw, unauthorized-inclusive set) instead of
`authorizedColumns()`. An unauthorized searchable column's value was reachable through global
search even though the column itself never rendered. Fixed to source from
`authorizedColumns()`; regression tests search for a value that exists **only** behind an
unauthorized direct column and an unauthorized relation column and assert zero results, revealing
neither the value nor the record's existence. See `tests/Feature/DynamicTable/AuthorizationLeakTest.php`.

## Filter authorization

Filters have no `toggleable()`/visibility state of their own by default — `Filter::visible()` is
optional. `TableDefinition::filter(string $key)` is the single authorization gate every caller
(query engine, `TableState` normalization, filter-panel rendering) routes through, resolved in
this order:

1. **Explicit filter visibility wins.** `TextFilter::make('internal_notes')->visible(fn () => ...)`
   — a filter-only field with no matching column declares its own condition.
2. **Otherwise, inherit from a same-key column.** `TextFilter::make('credit_limit')` where a
   `credit_limit` column also exists — if that column's `visible()` is `false`, the filter is
   unauthorized too, automatically, even if the column becomes unauthorized after the filter was
   defined. No separate authorization declaration is needed for the common case.
3. **Otherwise, default authorized** — matching `Column`'s own default-visible stance.

An unauthorized filter (however it becomes unauthorized) is removed from: Livewire state
(`TableState::normalizeFilters()` drops it), the rendered filter panel (`authorizedFilters()` is
what's passed to the view, never the raw filter set), saved-view application (re-normalized on
every apply), and query compilation (`TableQueryBuilder::applyFilters()` re-checks via
`definition->filter()`). See `tests/Feature/DynamicTable/AuthorizationLeakTest.php` for adversarial
coverage — including forcing an unauthorized filter directly into raw state and into a saved
view's stored configuration.

## Column manager and filter panel exposure

Both `column-manager.blade.php` and `filter-panel.blade.php` are handed **only**
`$definition->authorizedColumns()` / `$definition->authorizedFilters()` by the parent view — never
the full definition. An unauthorized column or filter's label never reaches rendered HTML, not
even as a disabled/greyed-out entry.

## State size limits

`TableState` enforces, in addition to `MAX_SEARCH_LENGTH` and `MAX_MULTI_SELECT`:

- `MAX_FILTERS = 20` — active filters beyond this are dropped during normalization.
- `MAX_SORTS = 5` — additional sort entries beyond this are dropped; a column appearing twice in
  a submitted sort list keeps only its first (highest-priority) occurrence.
- `MAX_PAGE = 100_000` — a requested page number above this is clamped, bounding worst-case
  offset-pagination cost.
- Duplicate entries in `visibleColumns`/`columnOrder`/`sorts` are deduplicated during
  normalization — `array_intersect()` on its own preserves duplicates from the untrusted input
  side, which was a real (non-security-critical, but correctness) gap.

`EloquentSavedTableViewStore::create()` additionally rejects a configuration payload larger than
20,000 bytes of JSON, so a saved view can't be abused to store an unbounded blob.

## SQL injection protection

- Every value reaching the database goes through Eloquent's parameter-bound `where()`/`whereIn()`/
  `whereBetween()` — never raw string interpolation or `whereRaw()`.
- Global search wraps its `LIKE` value in `addcslashes($text, '\\%_')` before binding, so a
  literal `%`/`_` in a search term can't be used to broaden a match.
- Verified with actual injection-shaped payloads (`' OR '1'='1`, `'; DROP TABLE ...; --`, UNION
  attempts) in `SecurityHardeningTest.php` — they're treated as literal search text, never as SQL.

## XSS protection

- Blade's `{{ }}` auto-escapes all rendered column values by default.
- No `HtmlColumn` or raw-HTML rendering path exists in this version (❌ Unsupported by design,
  per spec — would need to be an explicit, clearly-labeled trusted column type if ever added).
- `Column::getLink()` validates the URL scheme server-side: only `http`/`https` (or relative)
  schemes are returned; a `javascript:` (or any other) scheme from a `link()` callback is
  discarded before it ever reaches the view.

## Authorization scopes

`query()` is the one place tenant/company/permission scoping belongs — every generated query
starts from it and the query engine only ever *adds* `where`/`whereHas`/`orderBy` clauses on top,
never replaces or bypasses the base query. The global-search OR-group is nested inside a single
`where(function ...)` specifically so it can never widen the base scope via short-circuit OR logic.

## Preference & saved-view ownership

Both `TablePreferenceStore` and `SavedTableViewStore` implementations constrain every query by the
given `$user`'s own id — there is no method signature that accepts a raw user id from the client.
Cross-user access attempts (loading, saving, deleting, or promoting another user's preferences or
views) are silent no-ops, verified in `PreferencesTest.php` and `SavedViewsTest.php`.

## Export security

Not yet implemented (`exportable()` is a flag only — see [columns.md](columns.md)). When built, it
must reuse the exact same authorized `TableDefinition`/`TableState` path as the live table — never
a parallel, unscoped export query.

## Size/rate limits

- Search text: `TableState::MAX_SEARCH_LENGTH = 200`
- Multi-select (enum/BelongsTo filters): `TableState::MAX_MULTI_SELECT = 50`
- Per-page: allowlisted, `TableState::PER_PAGE_OPTIONS`
- Saved view name: truncated to 100 chars, empty name rejected (`ValidationException`)

## Fail-safe on malformed state

Every `normalize*` helper in `TableState` degrades gracefully: an unrecognized column, filter,
operator, direction, or value is dropped, not thrown as an error to the user. The only exceptions
thrown are **developer-configuration** errors (duplicate keys, invalid enum class, etc.) at
`TableDefinition`/column-definition time — never from live client input. No SQL error or stack
trace is ever surfaced to the browser from tampered state.
