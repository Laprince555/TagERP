# Copy-ready implementation prompt: Record References

Implement the Record References system in `D:\projects\TagERP` completely and verify it. Do not stop
at a proposal. Preserve unrelated work and never overwrite user changes. Follow `AGENTS.md`,
`CLAUDE.md`, `.ai/rules/index.md`, every path-matching project rule, and all relevant Laravel,
Livewire, Flux UI, Tailwind CSS, and Pest skills. Before changing version-sensitive code, confirm
installed package versions and search the version-specific documentation required by the project.

## Outcome

Build one shared, secure, high-performance way to present any Application record in three variants:

1. `card`: Application name/color/icon, record title, and important facts visible immediately.
2. `tag`: Application icon/color and record title; primary click opens the record, while a deliberate
   context-preview action loads and displays important facts in a compact anchored popover.
3. `icon`: Application icon/color only; primary click opens the record, while keyboard focus, touch
   details, or a delayed stable hover loads and displays the record title and important facts.

All variants must use the same Application-owned provider definition for the record title, facts,
route, query requirements, and authorization. Do not duplicate these definitions in views, Dynamic
Table columns, or future Dynamic Form fields.

Read and keep `docs/record-references/README.md`,
`docs/dynamic-table/record-references.md`, and
`docs/dynamic-form/record-references.md` accurate as implementation progresses.

## Repository facts and scope boundary

- Application metadata lives in `Modules/General/System/Application.php` and the original create-table
  migration lives in `Modules/General/Database/Migrations/2026_07_30_160000_create_applications_table.php`.
- Dynamic Table exists in `app/Support/DynamicTable`, `app/Livewire/DynamicTable`, and corresponding
  Blade components/tests.
- A shared Dynamic Form engine does not exist at the time this prompt was written. Confirm this again.
  If it is still absent, do not invent or fake an entire Dynamic Form framework. Deliver a stable,
  framework-agnostic renderer/provider API and keep the documented `RecordReferenceField` contract and
  integration plan accurate. If a real Dynamic Form engine now exists, integrate a typed field and
  test it using its established conventions.
- Shared cross-module contracts, DTOs, registry, palette resolver, rendering infrastructure, and
  preview host belong in root `app/`/`resources`. Each concrete Application provider stays inside its
  owning Nwidart module and registers through an explicit public boundary. Do not create `Apps/`
  directories or new packages.
- Do not add dependencies without explicit user approval.

## Mandatory Application color change

The project is still under development. Modify the original applications create-table migration;
do not create an `add_color_to_applications` migration.

- Add a required `string('color', 32)` column with no index and no silent fallback that hides missing
  seed data.
- Add `color` to the Application model's fillable attributes and use a backed enum/cast or an
  equivalent single trusted allowlist if consistent with project conventions.
- Define a semantic palette that supports at least `sky`, `indigo`, `violet`, `amber`, `emerald`,
  `rose`, `cyan`, `orange`, and `slate`.
- Locate every Application seeder, factory, fixture, raw insert, and upsert. Add an explicit valid
  color to every Application. Add `color` to every `upsert()` update list so rerunning a seeder can
  correct it.
- Update all tests and add a regression test proving every seeded Application has a non-null valid
  color and repeated seeding remains idempotent.
- For current World Applications, use this mapping unless an existing product decision contradicts
  it: countries `sky`, states `indigo`, cities `violet`, time zones `amber`, currencies `emerald`,
  languages `rose`, companies `cyan`, people `orange`.
- Do not store or render arbitrary Tailwind classes, CSS strings, or unchecked hex values. Resolve the
  token through a fixed trusted palette into foreground/background/border/focus/dark-mode variables.
  Never interpolate request/database text into a class name or raw style declaration.

Because the original migration changes, use the project's safe development reset/test database flow
for verification. Do not perform destructive database reset operations against an unverified or
non-test database.

## Required architecture

Create small, typed, independently testable concepts equivalent to:

- `RecordReferenceVariant` enum: `Card`, `Tag`, `Icon`.
- `ApplicationColor` enum or one canonical palette allowlist.
- `RecordFact`: escaped label/value with priority and optional safe presentation metadata.
- `RecordReferenceIdentity`: minimal initial payload.
- `RecordReferencePreview`: on-demand facts/status payload.
- `RecordReferenceProvider` contract plus an abstract base only if it removes real duplication.
- `RecordReferenceRegistry`: immutable Application code to trusted provider mapping.
- `RecordReferencePalette`: token to trusted design values.
- Stateless Blade components for the three visual variants.
- Exactly one lazy `RecordReferencePreviewHost` per page or parent Livewire boundary.

Do not make one Livewire component per record, table row, cell, card, tag, or icon. Do not put Eloquent
models, builders, providers, closures, or column objects in public Livewire state. Do not query in
Blade, accessors called by Blade, DTO constructors, fact formatters, or rendering providers.

Each concrete provider must own and explicitly declare:

- immutable Application code;
- fixed supported model class;
- record title construction;
- server-generated named record route;
- ordered allowlisted facts;
- minimal identity columns/relations;
- additional immediately visible card columns/relations;
- separate preview-only columns/relations;
- policy/gate, tenant, parent, active, and soft-delete scoping;
- optional cache policy, defaulting to no cross-request record caching.

The client may submit only an allowlisted Application code, bounded scalar record key, and normalized
record version. It must never submit a PHP/model/provider class, route name, relation path, database
column, SQL expression, template/view name, Tailwind class, or arbitrary color.

Implement Country (`gen-wld-ctr`) first as a vertical proof of concept. Complete and verify all three
variants and integrations before rolling providers out to additional Applications.

## Lazy data and request rules

Split initial identity from preview facts.

### Card

Card facts are visible, so fetch only the provider-declared card fields/relations in the same bounded,
paginated query that fetched the visible records. There must be no request or query per card and no
secondary preview request for already visible facts.

### Tag

Fetch identity only on initial render. Do not fetch preview fields or relations. Load the preview only
on right click/context menu, keyboard Context Menu/`Shift+F10`, or a discoverable touch details action.
A primary click follows the authorized record route.

### Icon

Fetch identity only on initial render. Do not fetch preview fields or relations. Load the preview on
keyboard focus, touch details, or after a stable hover delay of about 400 ms. Cancel before requesting
if the pointer leaves during the delay. A primary click follows the authorized record route.

### Shared preview host

- One host services all record references within its page/Livewire boundary.
- Resolve provider and model exclusively from the server registry.
- Validate input and authorize before returning any record metadata.
- Query only provider-declared columns and fixed relations.
- Return a minimal DTO, never a complete model/relationship payload.
- Deduplicate in-flight requests.
- Cache successful authorized results in page memory using Application code, record key, record
  version, locale, tenant, and permission/user scope as applicable.
- Reopening the same record/version in the same page lifetime must issue no second network request.
- Do not preload all previews, speculate on adjacent rows, or send hidden facts in initial HTML,
  Alpine data, Livewire state, or data attributes.
- Cross-request fact caching is disabled by default. If a provider opts in, prove its facts are safe to
  share within the cache scope, include tenant/locale/version/authorization scope in the key, set a
  bounded TTL, and implement invalidation.
- Application metadata may be cached/memoized and must reuse/integrate with existing navigation cache
  invalidation. Never query the applications table once per reference.
- Add client delay/cancellation/deduplication. Add a sensible server-side throttle if the selected
  transport supports it without weakening Livewire/CSRF conventions.

## Performance acceptance budgets

- Tag/icon initial table render: existing pagination count/data queries plus a constant number of
  required eager-load queries; zero preview queries.
- Card render: the same bounded constant-query model with only card-visible fields and relations;
  zero queries per rendered card.
- Application metadata: zero queries on cache hit and at most one bounded cache fill, never per row.
- First preview: one primary record query plus a constant number of declared preview relation queries.
- Same preview/version reopened on the same page: zero HTTP/Livewire requests and zero DB queries.
- A hidden Dynamic Table record-reference column: zero additional selects/eager loads.
- Query count must not grow with the number of rows. Add regression tests comparing small and large
  page sizes and inspect SQL/selects to prove preview-only data is absent initially.

Use pagination, select only required columns, include relationship matching keys, merge/deduplicate
requirements, and prevent lazy loading in tests/development. Do not use `SELECT *`, unbounded `get()`,
`all()`, per-row policy queries, per-row Application queries, or per-row network requests.

## Security acceptance requirements

- Authorize every preview as a server entry point before exposing title, URL, facts, or existence.
- Prevent IDOR with provider-owned fixed-model scoped queries and policies/gates.
- Enforce tenant, parent record, Application permission, inactive state, and soft-delete rules.
- Use named routes and scoped route binding for nested resources; generate URLs server-side.
- Do not derive a record URL by blindly concatenating an ID onto the Application route.
- Explicitly allowlist facts. Never call `toArray()` on a record for a preview.
- Exclude sensitive values from DOM, Livewire public state, response payloads, caches, logs, errors,
  and analytics.
- Escape title, labels, and values with Blade. Raw HTML is unsupported unless a later explicit audited
  feature introduces a safe value type.
- Validate/reject invalid variant, color, code, key type/length, and options.
- Return a generic unavailable/forbidden preview state without partial data leakage.
- Remove/disable the route when the user cannot view the record.
- Add adversarial tests for forged Application codes, keys belonging to another tenant/parent,
  unauthorized/deleted records, XSS payloads, invalid colors, invalid variants, and request flooding
  controls.

## UI and interaction requirements

Use Flux UI free components where available and stateless Blade/Alpine only for the missing anchored
context/hover behavior. Follow existing Tailwind v4 conventions. Support RTL, Arabic/English,
dark mode, keyboard, screen readers, touch, reduced motion, long text, and viewport collision.

- Card: restrained Application-colored accent, icon block, small Application name, prominent record
  title, two-to-four prioritized facts, optional semantic status, valid link semantics, no invalid
  nested interactive elements.
- Tag: compact icon/title chip; primary click navigates; context preview works by right click,
  keyboard, and discoverable touch details. Do not rely on right click alone.
- Icon: accessible hit target and label; primary click navigates; delayed hover/focus/touch details
  opens preview. Do not rely on hover alone.
- Preview surface: skeleton while loading, clear unavailable state, Escape/outside-click close, focus
  restoration, collision-aware positioning, and no layout shift in the table.
- Status and meaning must never be communicated by color alone. Verify contrast in light/dark themes.

## Dynamic Table integration

Implement a typed `RecordReferenceColumn`; do not return HTML from `formatUsing()`.

The column must allow a trusted Application code, trusted relationship/already-loaded record source,
variant, explicit scalar search/sort/filter backing fields, and explicit raw export behavior. Teach the
query builder/renderer to:

- apply initial requirements only when the column is visible;
- select identity only for tag/icon;
- include declared card facts for card;
- keep tag/icon preview requirements out of the initial table query;
- narrow related selects and include Eloquent owner/foreign keys;
- merge duplicate requirements across columns;
- render through the shared Blade component and one preview host;
- retain stable row/cell `wire:key` values;
- keep browser state allowlisted and primitives-only.

The existing Dynamic Table relation loader currently loads full related rows. Do not let the new
column use that to preload preview data. Add the targeted narrowing needed for record references and
test the generated selects and constant query count. Preserve current behavior for unrelated columns
unless a small safe refactor is required.

Update `.ai/dynamic-table/CHECKLIST.md` and `docs/dynamic-table/record-references.md` with delivered
paths, examples, query measurements, tests, and remaining limitations.

## Dynamic Form integration

Confirm whether Dynamic Form exists when implementing:

- If it exists, add a typed `RecordReferenceField` supporting all variants and the shared preview host.
  The submitted/persisted form value remains the intended scalar key; do not dehydrate the DTO,
  provider, Eloquent model, or preview facts. Treat record presentation separately from relation
  selection/search. Test create/edit/show, validation rerender, changed selection, deleted/inaccessible
  relation, authorization, and lazy preview reuse.
- If it does not exist, do not build a fake form engine. Ensure the shared renderer/provider public API
  can satisfy the documented field contract without Dynamic Table dependencies, keep
  `docs/dynamic-form/record-references.md` explicit about status, and provide a concrete future
  integration checklist.

## Tests and verification

Use Pest and existing conventions. At minimum add:

- schema/model/factory/all-seeder color tests;
- idempotent Application upsert tests that include color updates;
- provider/registry/DTO/palette unit tests;
- Country card/tag/icon render tests;
- authorization, tenant/parent scope, inactive/deleted/missing record tests;
- XSS and forged input tests;
- route generation and unauthorized-link omission tests;
- Dynamic Table visible/hidden column tests;
- SQL field-selection and fixed query-count regressions;
- proof that tag/icon previews are absent before user action;
- first-preview query/request test and repeat-preview no-request browser test;
- delayed-hover cancellation and in-flight deduplication tests where browser tooling allows;
- RTL/dark/mobile/keyboard/focus/Escape/collision behavior tests;
- Dynamic Form tests only if a real engine exists.

Run the narrowest relevant tests during development, then all affected suites. Run
`vendor/bin/pint --dirty --format agent` after PHP changes and the full relevant test suite before
finishing. Build frontend assets if the implementation changes compiled CSS/JS. Inspect the final diff
for unrelated changes and do not modify or commit user-owned unrelated untracked files.

## Documentation and handoff

Update all three referenced documentation files to match reality. Document:

- final namespaces and public APIs;
- how an Application registers a provider and chooses a color;
- how to define facts and query requirements;
- Blade, Dynamic Table, and actual/future Dynamic Form examples;
- lazy preview lifecycle;
- security boundaries and cache rules;
- measured initial/card/preview/repeat query and request counts;
- accessibility behavior;
- tests run and results;
- migrations/reset requirement for this development-only schema change;
- any intentional deviation, tradeoff, or remaining limitation.

Finish with a concise implementation summary, changed-file list, test evidence, measured budgets,
security review findings, and clearly separated follow-ups. Do not report completion while any required
variant, authorization control, performance test, Dynamic Table integration, or documentation update
remains unfinished.

