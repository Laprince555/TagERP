# Copy-ready remediation prompt V2: finish and harden Record References

Work in `D:\projects\TagERP` and finish the existing Record References feature. This is a narrow
remediation and completion task, not a greenfield rewrite. Preserve unrelated/user-owned work in the
dirty worktree and never revert broad areas to make this task easier. Re-read files immediately before
editing because this workspace has previously had concurrent Dynamic Table work.

Follow `AGENTS.md`, `CLAUDE.md`, `.ai/rules/index.md`, every matching project rule, and the relevant
Laravel, Livewire, Flux UI, Tailwind CSS, and Pest skills. Confirm installed package versions and use
the project's version-specific documentation search before changing version-sensitive code. Do not
add dependencies, edit `vendor/`, or create a new top-level architecture without approval.

## Current verified baseline — preserve it

The following work already exists and must be improved in place, not recreated:

- `applications.color` is required in the original create-table migration. There must not be an
  `add_color_to_applications` migration because the product is still under development.
- `ApplicationColor` is an allowlisted enum; `Application` casts `color` to it.
- `ApplicationFactory` supplies a valid color, and every current World Application seeder entry has a
  deliberate valid color. The seeder upsert updates `color` and remains idempotent.
- The shared Record Reference foundation exists: palette, identity/fact/preview DTOs, provider
  contract, registry, resolver, and access service.
- Country uses the real `Nnjeim\World\Models\Country` model and the package's table/data. Do not
  create a duplicate Country model, migration, factory dataset, or replacement package.
- Card, Tag, and Icon Blade variants exist.
- One Livewire preview host is mounted in the application layout.
- Dynamic Table has a typed `RecordReferenceColumn` and the real Countries index uses it.
- `general.world.countries` and `general.world.countries.show` routes/pages exist.
- Country has a Dynamic Record View page.
- Dynamic Form does not currently exist. Its document correctly describes a future contract; do not
  fabricate a Dynamic Form engine merely to claim integration.

Audit baseline on 2026-08-14:

- Record Reference + Dynamic Table + Dynamic Record View targeted suites: 285 tests, 574 assertions,
  all passing.
- Full suite: 326 tests, 733 assertions, all passing twice consecutively.
- The previous Flux/Blade `unexpected endif` compilation failure is already fixed. Do not reintroduce
  conditional Blade directives inside `<flux:table.column ...>` attributes.

Green tests are not sufficient by themselves: several required paths below currently have no test.

## Current verified gaps

1. Country preview uses `RecordReferenceAccess`, but the Countries index and show pages only have the
   `auth` middleware. They do not consistently enforce the owning Application's `is_active` and
   `permission_name` rules. A crafted Livewire request must not bypass page-level authorization.
2. `CountryRecordReferenceProvider::scopeQuery()` currently returns the unscoped query even though
   the model has `status` and `authorize()` only accepts `status = 1`. Scoping and authorization can
   therefore drift.
3. Related `RecordReferenceColumn`s are eager loaded through plain `with($path)`, which loads every
   related column, including preview-only/unneeded data. Provider scope is not applied to the eager
   load.
4. Dynamic Table does not validate that a self-reference row or related model matches the provider's
   `modelClass()`. Invalid developer configuration can reach rendering instead of failing clearly.
5. Existing query-count tests call `TableQueryBuilder::paginate()` only. They do not measure the real
   Livewire + Blade render, Application metadata lookup, provider authorization, or relation case.
6. Record Reference resolution/access/provider dispatch still occurs inside the Dynamic Table Blade
   row loop through `app(...)`. It is query-free today for Country, but the view is still responsible
   for business/presentation preparation and cannot prevent a future querying provider from creating
   an N+1.
7. The Tag variant nests a `<button>` inside an `<a>`, which is invalid interactive HTML. Its mobile
   preview target is also too small.
8. The Icon variant binds preview to `touchstart` on the same anchor that navigates on click; one touch
   may preview and navigate. Its interactive target is 32px instead of the desired minimum 44px.
9. The Alpine preview cache key is only `applicationCode:recordKey`. It has no locale/access/context
   version. Failed promises have no `catch/finally`, so `loading` and `inflight` may remain stuck.
10. Preview requests have a response race: opening A then B can allow A's slower response to replace
    B. Closing while a request is in flight can also cause stale focus/data updates.
11. Preview positioning uses raw anchor coordinates without viewport clamp/flip, scroll/resize
    repositioning, or RTL-aware placement. There is no visible close action for touch users.
12. Record Reference accessibility text, preview empty/error text, and Country fact labels are
    hard-coded in English rather than localized.
13. JavaScript event details interpolate values into quoted JavaScript strings. Use a JSON-safe Blade
    mechanism such as `@js(...)`; do not rely on HTML escaping as JavaScript escaping.
14. Dynamic Record View still has no typed Record Reference field/content. The Country page using the
    Dynamic Record View engine is not the same as the engine being able to render Card/Tag/Icon
    references inside its field/content definitions.
15. `RecordReferenceProvider::cacheTtl()` and `RecordReferencePreview` are not meaningfully used. Do
    not retain dead public API: either implement it safely with scoped/versioned keys and reliable
    invalidation, or remove it and document client-only request deduplication.
16. Documentation is stale: it describes metadata as fetched from Blade, contains obsolete
    limitations, and `.ai/dynamic-table/CHECKLIST.md` marks Record Reference work both complete and
    planned.

## Required implementation

### 1. Establish one consistent access policy

Use `RecordReferenceAccess` (or a narrowly improved equivalent) as the single Application-level
decision point for initial reference rendering, preview, Countries index, Countries table Livewire
requests, and Country show.

The policy must require:

- an authenticated actor;
- an existing active Application;
- `permission_name` when configured;
- the provider's query scope;
- the provider's pure/query-free record decision where a record is already loaded;
- tenant/parent scope when an owning provider actually has such context. Do not invent a fake tenant
  rule for Country.

Choose one documented read-denial status for index/show (prefer a non-enumerating 404 if consistent
with the existing Dynamic Record View convention). Preview must always return the same generic
unavailable shape for missing, forged, inactive, or unauthorized input.

Authorization must be rechecked on every Livewire request/action, not only during the initial page
mount. An already-mounted component must stop returning data if the Application is disabled or the
actor loses permission.

Improve request-scoped Application metadata/access memoization so all distinct visible Application
codes are fetched in one bounded query per render/request. Do not put database queries in Blade.

For Country specifically, make `scopeQuery()` enforce the same `status = 1` rule used by
`authorize()`, then have preview/index/show use that provider-owned scope rather than independently
duplicating rules that can drift.

### 2. Move Record Reference cell preparation out of Blade

Before rendering the table view, prepare typed, immutable render data for visible Record Reference
cells, keyed by row key and column key. The Blade component should only choose/render the already
prepared Card/Tag/Icon component; it must not resolve services, query metadata, decide access, or call
provider business methods inside the row loop.

Requirements:

- only visible and authorized Record Reference columns contribute work;
- one Application metadata lookup for all distinct visible codes, never one per row;
- provider `authorize()` is documented and tested as query-free;
- denied/missing references render no title, facts, URL, or existence signal;
- Card data includes only declared identity + card requirements;
- Tag/Icon initial data never includes preview-only facts/relations;
- no model or full `toArray()` payload is serialized into DOM/Livewire state.

Use a small presenter/view-model service if useful, but do not create a second competing registry or
resolver.

### 3. Make Dynamic Table relation loading precise and safe

For a self-reference:

- validate that the table query model is the provider's `modelClass()`;
- select the model key + provider identity columns; add card columns only for Card;
- never select preview-only columns for Tag/Icon.

For a related reference:

- support and test at least a first-level `BelongsTo` relation;
- include the parent foreign key and related owner key needed by Eloquent matching;
- constrain the eager-loaded related select to its key + identity columns, plus card columns only for
  Card;
- apply `provider->scopeQuery()` to that eager-load query;
- validate the related model class against `provider->modelClass()`;
- merge requirements when multiple visible columns use the same relation;
- never silently use an unconstrained `with($path)` for a Record Reference.

Either fully support dotted/nested relation paths with correct matching keys at every depth and tests,
or reject them early with a dedicated configuration exception. Do not document dotted paths as
supported while the engine only handles a first-level relation.

A hidden Record Reference column must cause zero reference-specific select fields, eager loads,
Application metadata queries, provider calls, or preview work.

### 4. Add real Dynamic Record View integration

Add a typed Record Reference integration following the current field architecture. Prefer a
`RecordReferenceViewField extends Field` unless close inspection shows a typed content block is a
cleaner fit. Do not return raw HTML from `display()` or `formatUsing()`.

The typed API must support:

- trusted developer-declared `applicationCode`;
- `RecordReferenceVariant` (`Card`, `Tag`, `Icon`);
- self-record or an already-resolved relation/path;
- the shared provider, access service, resolver/presenter, palette, Blade variants, and one shared
  preview host;
- visibility and column-span conventions already owned by `Field`;
- clear failure for a provider/model mismatch.

Extend the Dynamic Record View renderer with a typed branch and prepare all Application/reference
render data before Blade. Do not run relation or Application queries inside
`fields-content.blade.php`.

Related records must be eagerly loaded with only required matching/identity/card columns. If the
current Dynamic Record View architecture cannot safely infer relation requirements, introduce the
smallest explicit query-requirement API and make missing eager loading fail in development/tests
instead of lazy-loading silently.

Add a realistic test fixture/definition exercising all three variants. If no natural production
Country relationship exists, do not add a nonsensical self-reference to the Country UI merely as a
demo; prove the reusable engine through focused test models/definitions and document a real usage
example.

### 5. Repair Tag and Icon semantics and interaction

Tag:

- do not nest a button inside an anchor;
- use a semantic wrapper with sibling navigation and preview controls, or another valid accessible
  structure;
- normal click/Enter navigates to the record;
- right-click, Context Menu key, and `Shift+F10` open preview without navigating;
- touch users get an explicit preview action with at least a 44x44 CSS-pixel hit target;
- keep the visible tag compact even if the transparent/adjacent hit area is larger.

Icon:

- retain delayed stable hover and keyboard-focus preview;
- normal click/tap navigation and touch preview must be unambiguous;
- never preview and navigate from the same gesture;
- provide an explicit accessible touch preview control or a carefully tested long-press contract;
- make the interactive target at least 44x44 while allowing the visible icon chip to remain smaller.

Both variants:

- use `@js(...)` or an equivalent JSON-safe encoding for event values;
- keep URLs server-generated through named routes;
- omit the link element entirely when URL is null—never `href=""`;
- retain Blade escaping and Alpine `x-text`; no raw fact/title HTML;
- provide localized English and Arabic labels/instructions.

### 6. Harden the shared preview host

Keep exactly one host per page and keep previews lazy: no request until the user deliberately hovers
for the configured delay, focuses, invokes the context menu, or presses the explicit touch preview
control.

Client behavior must:

- deduplicate concurrent requests for the same scoped key;
- use a server-provided, non-sensitive context/version token covering at least locale, actor/access
  scope, tenant when present, and provider/application version, or use an equally safe documented
  invalidation design;
- clear page cache on Livewire navigation/context changes;
- not permanently cache transient failures, rate-limit responses, or rejected promises;
- use `try/catch/finally` so `loading` and `inflight` always recover;
- ignore stale responses using a monotonically increasing request/open token;
- not update data/focus after the preview has closed or another record became active;
- handle a null preview URL by rendering non-link title text;
- clamp horizontally, flip above the trigger when needed, remain inside viewport margins, and
  recalculate on relevant scroll/resize events;
- work in LTR and RTL;
- restore focus to the initiating control and expose a visible localized close action on touch.

Server behavior must:

- validate bounded scalar input and resolve providers only through the trusted registry;
- apply the unified access/provider scope before returning identity or facts;
- select only key + identity + preview requirements;
- return a minimal DTO/array—never a model or unrestricted `toArray()`;
- keep a configurable bounded rate limit keyed appropriately for the authenticated ERP context;
- return the same generic unavailable payload for all denied/missing/forged cases.

Resolve the unused cache API honestly:

- If cross-request preview caching is implemented, keys must include schema/provider version, locale,
  actor/tenant/access scope, Application version, and record version. Implement reliable invalidation
  and never cache authorization decisions across actors.
- If reliable invalidation is not available for the package Country model, keep cross-request caching
  disabled, remove or clearly use the opt-in `cacheTtl()` contract, and rely on request memoization +
  scoped client deduplication. Do not leave a public method that nothing calls.

Use `RecordReferencePreview` as the canonical response DTO with an explicit serialization method, or
remove it if it adds no value. Do not retain a misleading unused DTO.

### 7. Localization and visual quality

Add project-conventional English and Arabic translations for:

- record preview label;
- preview unavailable/error/loading text where visible;
- open preview and close preview accessible labels;
- Tag context-menu instructions;
- Country fact labels such as Region, Subregion, Phone Code, and ISO3;
- any new Dynamic Record View field labels introduced by this integration.

Preserve the Application color allowlist and literal Tailwind class map. Never interpolate arbitrary
database/request values into Tailwind classes or CSS. Preserve dark mode, logical RTL spacing, clear
focus-visible states, and reduced-motion-friendly behavior.

### 8. Keep Dynamic Form status truthful

Re-scan the repository before finishing:

- If no Dynamic Form engine exists, do not create placeholder production classes. Keep
  `docs/dynamic-form/record-references.md` explicitly marked as planned and update its contract to
  reuse the final shared presenter/access/query-requirement design.
- If a real Dynamic Form engine appeared concurrently, integrate a typed `RecordReferenceField`
  using its actual conventions and add equivalent security/performance tests. Do not infer an engine
  from documentation alone.

### 9. Correct documentation and checklist state

Update documentation to match delivered code exactly:

- `docs/record-references/README.md`: architecture, access flow, lazy lifecycle, request/cache
  scoping, supported interactions, query budgets, and honest limitations;
- `docs/dynamic-table/record-references.md`: self and related examples, supported relation depth,
  constrained selects/eager loads, hidden-column behavior, and real render measurements;
- add/update `docs/dynamic-record-view/record-references.md` and link it from the Dynamic Record View
  documentation index/fields documentation;
- keep `docs/dynamic-form/record-references.md` truthful;
- reconcile `.ai/dynamic-table/CHECKLIST.md` so current status is stated once. Preserve useful history
  but clearly label historical measurements; remove contradictory current DONE/PLANNED claims.

Delete or correct claims that Application metadata is fetched from Blade, that unsupported nested
relations work, or that tests exist when they do not.

## Mandatory tests

Use Pest and existing project conventions. Add focused coverage for at least:

### Access/security

- guest, inactive Application, missing Application, denied `permission_name`, inactive Country,
  missing record, and forged Application code;
- consistent outcomes across initial cell rendering, preview, Countries index, Countries table
  Livewire requests/actions, and Country show;
- access revoked after mount is enforced on the next Livewire request;
- provider scope excludes inactive Country before it is returned;
- malicious titles/fact labels/fact values/application codes/record keys cannot inject HTML or JS;
- rate limiter blocks excess calls without leaking record existence and can be reset between tests.

### Dynamic Table

- Tag/Icon self-reference SQL omits card/preview-only columns;
- Card includes only declared card columns;
- hidden Record Reference column creates zero reference-specific work;
- first-level `BelongsTo` Tag/Icon selects only matching key + identity columns and applies provider
  scope;
- related Card adds card columns but not preview-only columns;
- provider/model mismatch and unsupported relation depth fail early with clear exceptions;
- multiple reference columns merge Application and relation requirements;
- a complete Livewire + Blade render with 10 rows and 50 rows has the same query count;
- a complete related-reference render has constant queries and no lazy-loading violation;
- Blade compilation regression for the Flux header remains covered.

### Dynamic Record View

- typed Record Reference field/content renders Card, Tag, and Icon;
- uses one shared preview host and the shared access decision;
- denied references emit no identity/facts/link;
- relation requirements are eager loaded and narrowed;
- render query count is constant and no query runs from Blade;
- stale/mismatched provider configuration fails clearly.

### UI/preview

- Blade output contains no nested interactive element (`button` inside `a`) and no empty href;
- accessible labels are localized;
- initial Tag/Icon HTML contains no preview facts;
- promise cleanup, stale-response protection, and scoped cache-key behavior are tested at the closest
  available layer;
- if Pest Browser is already installed/configured, add keyboard, touch, viewport-edge, RTL, dark-mode,
  repeat-open deduplication, and JavaScript-error tests;
- if browser testing is not configured, do not add a dependency without approval. Document and
  execute a precise manual browser QA checklist instead.

Tests must measure the actual render path, not only query construction. Avoid brittle absolute query
counts when framework bookkeeping varies; assert constant growth, selected columns, absence of
preview queries before action, and explicit upper budgets for the current implementation.

## Verification sequence

1. Re-read the final diff against this prompt and all applicable rules.
2. Run `vendor/bin/pint --dirty --format agent` after PHP edits.
3. Run the narrowest new/changed Pest files first.
4. Run all Record Reference, Dynamic Table, and Dynamic Record View suites together.
5. Run the complete test suite twice consecutively. The current baseline is 326 tests / 733
   assertions; the final count may increase, but there must be no regressions or order-dependent
   failure.
6. Compile Blade views (`php artisan view:cache`) to catch Flux/Blade parser regressions, then clear
   generated view cache if that is the project's normal local workflow.
7. Run the existing frontend build if UI assets/classes changed and dependencies are already
   installed.
8. Re-check `git status` and report only files intentionally changed for this task. Do not claim
   unrelated dirty files.

## Definition of done

The task is complete only when all of the following are true:

- Application color schema/factory/seeders remain correct with no new color migration;
- Card, Tag, and Icon are valid, accessible, localized, responsive, dark-mode/RTL-safe, and all use
  named record routes;
- Tag/Icon preview data is fetched only after a deliberate action and repeat opens are deduplicated
  safely;
- no request race, rejected promise, close-while-loading action, or touch gesture leaves stale UI or
  causes accidental navigation;
- index/show/preview/table/Dynamic Record View enforce one fail-closed access policy;
- no database query or provider business decision occurs inside Blade loops;
- self and related Dynamic Table references select only required columns with constant query counts;
- hidden references cause zero reference-specific work;
- Dynamic Record View has a real typed Record Reference integration for all three variants;
- Dynamic Form status is honest based on whether an engine truly exists;
- documentation/checklist match delivered behavior and measured evidence;
- targeted suites and two consecutive full suites pass;
- the final report lists changed files, query/request measurements, security decisions, test counts,
  remaining honest limitations, and any browser checks that could not be automated.
