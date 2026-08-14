# Package Extraction Readiness

This engine was built inside `app/` deliberately, with extraction boundaries in mind, but it has
**not** been extracted into a standalone Composer package. This page tracks what's already
package-ready and what would need to change before extraction.

## Already package-ready

- **`Core` layer** (`app/Support/DynamicTable/Core/`): zero imports of `Modules\*` or any concrete
  application Eloquent model. Verified structurally — see `tests/` (no `Modules` reference exists
  in this namespace) and enforced by convention, not yet by an automated architecture test (see
  "Remaining blockers" below).
- **`Query` layer** (`app/Support/DynamicTable/Query/`): depends only on `Core` and Eloquent —
  no Livewire, no Flux, no application models.
- **Storage behind contracts**: `TablePreferenceStore` and `SavedTableViewStore` are interfaces in
  `Core`; the Eloquent implementations live in `app/Support/DynamicTable/PreferenceStores/` and
  are bound via `AppServiceProvider`, not hard-coded into `Table`.
- **Rendering separated from query logic**: `Table` (Livewire) and the Blade views depend on
  `Core`/`Query`, never the reverse.
- **No `env()` usage** — the little configuration that exists (per-page allowlist, max search
  length) lives as constants on `TableState`, not scattered `env()` calls.

## Remaining application dependencies (extraction blockers)

| Blocker | Where | Effort to fix |
|---|---|---|
| `EloquentTablePreferenceStore`/`EloquentSavedTableViewStore` depend on `App\Models\User`-shaped `Authenticatable`, and the migrations live in the app's own `database/migrations/` | `app/Support/DynamicTable/PreferenceStores/`, `database/migrations/2026_08_14_16*`, `2026_08_14_17*` | Low — already behind contracts; a package would ship these as default implementations with publishable migrations |
| `AppServiceProvider` manually binds both contracts | `app/Providers/AppServiceProvider.php` | Low — becomes the package's own service provider |
| No dedicated config file yet — `PER_PAGE_OPTIONS`, `MAX_SEARCH_LENGTH`, `MAX_MULTI_SELECT` are `TableState` constants | `Core/TableState.php` | Low-medium — move to a `config/dynamic-table.php` published by the package provider |
| No translation file — labels/messages are inline strings/`__()` calls scattered through Blade views | `resources/views/components/dynamic-table/*.blade.php` | Medium — extract to `lang/vendor/dynamic-table/*.php` |
| Views live in the app's own `resources/views/` | `resources/views/livewire/dynamic-table/`, `resources/views/components/dynamic-table/` | Low — package would publish these, app overrides via Laravel's standard view-override mechanism |
| Eager loads not select-narrowed per relation (payload bloat, not correctness) | `TableQueryBuilder::applyEagerLoads()` | Medium — needs per-relation-type introspection to narrow `select()` inside `with()` |
| Relation sort limited to `BelongsTo` + aggregated to-many; no `HasOne`, no multi-level paths | `TableQueryBuilder::applyRelationSort()` | Medium-high — needs a general relation-path walker |
| Cursor pagination unimplemented | `TableQueryBuilder` | Medium |
| `exportable()` is a flag with no exporter | `Core/Column.php` | Medium-high (a full export milestone) |
| No row/bulk action framework | — | Medium-high |
| No summaries (count/sum/avg/min/max) | — | Medium |

**Resolved since the original version of this table** (kept as a record, not a current blocker):
`ComputedColumn`'s fluent-order dependency was fixed via `Column::validate()` (order-independent
now); a `SearchDriver` contract with `DatabaseSearchDriver`/`ScoutSearchDriver` exists; an
architecture test suite now enforces the Core/Query namespace boundaries
(`tests/Feature/DynamicTable/ArchitectureTest.php`); the `BelongsToFilter` UI is a real debounced
async picker, not a plain ID input.

## If extraction happens

1. Create a new Composer package repo (`vendor/dynamic-table`), move `Core`/`Query`/`PreferenceStores`
   verbatim (already dependency-clean).
2. Write a `DynamicTableServiceProvider` that: binds the two store contracts, publishes migrations,
   publishes views under a `dynamic-table::` namespace, publishes a config file, registers
   translations.
3. Update this app's `app/Livewire/DynamicTable/*` and views to `use Vendor\DynamicTable\...`
   instead of `App\Support\DynamicTable\...` — a mechanical namespace change since the app already
   only depends on `Core`/`Query`'s public API.
4. Add the architecture test from the table above before/during extraction so the boundary is
   enforced going forward, not just by convention.
5. Publish to Packagist (or a private repo) and add it as a real Composer dependency; run this
   app's full `DynamicTable` test suite against the extracted package as a smoke test.

None of this is required for the engine to work correctly inside this application today — it's
a forward-looking checklist for the day extraction is actually needed.
