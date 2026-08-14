# Copy-ready remediation prompt: complete and harden Record References

Continue the existing Record References implementation in `D:\projects\TagERP` and fix every
verified gap below. This is a remediation task, not a greenfield rewrite. Preserve unrelated and
user-owned changes, especially the currently uncommitted Dynamic Table and Dynamic Record View work.
Do not revert, replace, or broadly redesign working subsystems unless a narrowly scoped refactor is
required to meet the acceptance criteria.

Follow `AGENTS.md`, `CLAUDE.md`, `.ai/rules/index.md`, every path-matching project rule, and the
relevant Laravel, Livewire, Flux UI, Tailwind CSS, and Pest skills. Confirm installed versions and use
the project's version-specific documentation search before code changes. Do not add dependencies or
edit files under `vendor/` without explicit user approval.

## Existing implementation to retain

The following foundations already exist and must be improved rather than recreated:

- required allowlisted `applications.color` in the original create-table migration;
- `ApplicationColor`, `RecordReferencePalette`, DTOs, provider contract, registry, and resolver;
- Country provider backed by the existing `Nnjeim\World\Models\Country` model/table/data;
- stateless Card, Tag, and Icon Blade variants;
- one shared Livewire preview host in the app layout;
- typed Dynamic Table `RecordReferenceColumn`;
- Country Dynamic Record View show page and route;
- Record Reference, Dynamic Table, Dynamic Record View, and Dynamic Form documentation;
- current tests.

Do not create a duplicate Country model, countries table migration, Country seed dataset, or custom
replacement for the `nnjeim/world` package. Do not create a second color migration. Keep all current
Application color mappings and strengthen their regression coverage where needed.

## Verified defects to fix

1. `resources/views/components/dynamic-table/table.blade.php` queries Applications from Blade.
2. Every rendered Country identity calls `CountryRecordReferenceProvider::authorize()`, which queries
   the Application again, causing an N+1 during the Blade render. Existing query-count tests measure
   `paginate()` only and miss rendering.
3. Hidden Record Reference columns can still trigger the Application metadata query because codes are
   collected from all definitions, not only visible authorized columns.
4. Related Tag/Icon references use unconstrained `with($path)`, loading complete related rows and
   preview-only fields before user action.
5. Preview authorization checks only Application `is_active`; it does not consistently enforce
   authenticated user, `permission_name`, tenant/parent scope, Country status, or the same access rule
   used by the show route.
6. The Country show route is authenticated but its record query is otherwise unscoped.
7. Unauthorized identities still render record metadata and `href=""`; the link is not actually
   omitted.
8. The preview cache key is only `applicationCode:recordKey`; it omits version, locale, and access
   scope.
9. Preview actions have no abuse/rate guard and rejected client promises can leave in-flight/loading
   state stuck.
10. Tag markup nests a button inside an anchor. Icon touch behavior may preview and navigate from the
    same gesture. Hit targets, translations, focus restoration, and viewport collision handling are
    incomplete.
11. The seeded Application route `general.world.countries` has no real application index route/page;
    only `general.world.countries.show` exists.
12. Dynamic Table has a typed column but no real production Country index table uses it.
13. Dynamic Record View has no typed Record Reference field/content integration.
14. Dynamic Form does not exist. Documentation correctly admits that, but no code may claim that its
    integration is delivered.
15. `.ai/dynamic-table/CHECKLIST.md` contradicts itself by marking the integration both DONE and
    PLANNED and claims hidden-column tests that do not exist.
16. The full test suite is not stable: 257/258 passed during audit, with
    `FilterOperatorUiTest` failing in the full run but passing by itself, indicating order-dependent
    state or test isolation.

## Required outcomes

### 1. Create one query-free access and metadata boundary

Move every database query out of Blade. Introduce or reuse a focused service/presenter that prepares
Record Reference render data before views receive it.

The implementation must:

- resolve all distinct **visible and authorized** Application codes in one bounded query or trusted
  cross-request navigation/application metadata cache;
- select only required Application metadata (`id`, `code`, translated name fields, `icon`, `color`,
  `is_active`, `permission_name`, and required ownership keys);
- memoize access decisions per Application/current actor/tenant for the current request;
- enforce `is_active` and `permission_name` consistently with `NavigationTreeService`;
- avoid any policy or Application query inside a per-row loop;
- keep record-level authorization pure/query-free when evaluating an already-loaded row;
- return `null`/an unavailable DTO for unauthorized identity instead of a DTO containing title/facts
  with a null URL;
- ensure views receive prepared identities/facts only and contain no Eloquent or registry queries.

Prefer one explicit public service/contract over static state. Do not store user-specific authorization
inside the global registry or a process-lifetime singleton. The provider registry itself is stateless;
fix its binding/registration lifecycle so it works correctly under normal PHP requests and long-lived
workers. A scoped registry whose instance is flushed while module `boot()` does not rerun is not an
acceptable Octane-safe design.

### 2. Enforce one authorization rule everywhere

Preview, initial Card/Tag/Icon render, Dynamic Table, Countries index, and Country show route must use
the same access boundary.

At minimum:

- require an authenticated user for all Record Reference preview actions in this authenticated ERP;
- deny an inactive Application;
- enforce `permission_name` when present and fail closed if permission evaluation errors;
- apply tenant and parent scopes when a provider declares them;
- define and test the intended Country record-status rule; unless an existing product rule says
  otherwise, only `status = 1` Countries are referenceable/showable;
- return the same generic unavailable/not-found behavior without leaking whether a denied record
  exists;
- generate URLs server-side through named routes;
- never expose title, URL, facts, or stale cached facts for a denied identity;
- render no anchor when URL is absent. Use a non-interactive semantic element or render nothing,
  according to the caller's documented behavior. Never use `href=""` as an authorization fallback.

Add tests for guest preview attempts, authenticated users without required permission, inactive
Application, inactive Country, missing Country, forged Application code, cross-tenant/parent fixtures
where the project has such context, and consistency between preview and show-route access.

### 3. Remove N+1 and prove the complete render budget

Fix Country authorization so it never queries the Application for each row. Move Application loading
and access evaluation outside the row loop.

Add end-to-end query-count tests that render the actual Dynamic Table/Blade/Livewire output, not only
`TableQueryBuilder::paginate()`:

- render 10 and 50 Tag rows and assert the same query count;
- render 10 and 50 Card rows and assert the same query count;
- assert there is no Application query per row;
- assert a hidden Record Reference column performs no Application metadata query and adds no record
  select/eager load;
- assert multiple visible columns for one Application deduplicate metadata and relation requirements;
- keep existing pagination count/data query assertions.

No queries may occur from Blade, a formatter, DTO, accessor used by rendering, or provider fact/title
methods. Enable lazy-loading prevention in the relevant tests so undeclared relationships fail loudly.

### 4. Make lazy loading correct for self and related references

Refine the provider/query requirement contract so columns and relations are explicit and cannot be
confused. Support separate requirements for:

- initial identity columns/relations;
- immediately visible Card columns/relations;
- on-demand preview columns/relations.

For Tag/Icon:

- initial self-reference queries select identity only;
- initial belongs-to reference eager loads select only related primary/owner keys plus identity fields;
- preview-only fields and relations must not appear in initial SQL or loaded attributes;
- no `SELECT *` related eager load is allowed.

For Card:

- include only identity and Card-visible requirements in the existing bounded page query;
- do not issue per-card requests or queries.

For Preview:

- load only identity plus preview requirements after the deliberate user action;
- constrain relation selects while retaining every Eloquent key needed to hydrate relationships;
- merge and deduplicate identical requirements;
- explicitly reject unsupported relation types/depths with configuration exceptions rather than
  silently falling back to full-row loading.

Validate that a `RecordReferenceColumn`'s resolved record model matches the provider's model class.
Add self-reference, belongs-to, hidden-column, Card, Tag, and Icon SQL/loaded-attribute tests.

### 5. Correct preview caching, deduplication, and abuse controls

Add a provider-owned record version contract. Use `updated_at` where reliable. For timestamp-less,
mostly immutable package records such as Country, define an explicit reliable dataset/version token
or disable reusable caching if no correct version can be supplied; do not pretend an ID alone is a
version.

The page-memory cache and in-flight key must include:

```text
application code + record key + record version + locale + tenant/access scope
```

Requirements:

- reopening the same authorized version in the same page makes zero new Livewire/HTTP requests;
- a version, locale, user/tenant, permission, or selected-record change cannot reuse stale preview;
- failed/rejected requests clear loading and in-flight state via `try/finally` and show a generic safe
  error state;
- cancel delayed hover before the request if the pointer leaves;
- add a bounded per-user/session/IP preview rate guard using project/Laravel conventions without
  weakening Livewire CSRF/session protections;
- keep cross-request fact caching disabled by default; if any provider opts in, include all access and
  version dimensions and implement invalidation.

Do not preload adjacent/visible previews and do not serialize facts into initial DOM attributes,
Alpine state, or Livewire public state.

### 6. Repair the three UI variants

Keep the existing visual direction and Application palette, but fix semantics and accessibility.

Card:

- valid link semantics with no nested controls;
- non-link fallback when URL is absent;
- two-to-four prioritized, translated facts;
- long-title truncation/wrapping, RTL, dark mode, contrast, and focus-visible states.

Tag:

- do not nest a button inside an anchor;
- use sibling navigation and preview controls within a semantic wrapper, or another valid accessible
  structure;
- preserve left/primary navigation plus right-click, Context Menu key/`Shift+F10`, and a discoverable
  touch preview action;
- ensure triggering preview never accidentally navigates.

Icon:

- keep the visual icon-only requirement;
- provide at least a 44x44 CSS-pixel interactive hit target;
- make desktop primary activation and touch preview/navigation behavior unambiguous;
- do not open a preview and navigate from the same gesture;
- provide an accessible name and keyboard access without hover dependency.

Preview surface:

- use Flux free components where appropriate; do not use Pro-only components;
- translate every visible/ARIA string through Laravel localization;
- position relative to the actual triggering anchor, clamp horizontally/vertically, and flip above
  when there is insufficient space below;
- use the event's anchor for correct focus restoration;
- close on Escape and outside activation;
- handle reduced motion and loading/unavailable/error states;
- avoid layout shift in tables.

Add Blade rendering tests for valid unauthorized markup and escaping. If Pest Browser is configured,
add browser tests for navigation, right click, keyboard, focus restoration, touch behavior, request
deduplication, dark mode, RTL, and viewport edges. If it is not configured, do not add a dependency;
perform the equivalent manual in-app browser verification and document exact evidence and any
remaining unautomated risk.

### 7. Deliver a real Countries index using Dynamic Table

Create the missing application-owned Countries index route/page named exactly
`general.world.countries`, matching `WorldApplicationsSeeder`. Reuse the package Country model/table
and the existing Dynamic Table engine.

The real Countries table must:

- be authenticated and use the same Application/record access boundary;
- be paginated and explicitly select required columns;
- use `RecordReferenceColumn` in a real production definition, not tests only;
- default to the most appropriate compact variant (normally Tag), link to
  `general.world.countries.show`, and expose the configured important facts lazily;
- provide explicit scalar search/sort/filter fields without trusting browser SQL/relations;
- avoid loading all Countries or preview facts upfront;
- make the seeded launcher Application route resolve to a real URL.

Add route, Livewire, authorization, query-count, search/sort/pagination, and record-link tests.

### 8. Add Dynamic Record View integration honestly

Add a typed, reusable Record Reference field/content type to the existing Dynamic Record View engine
so a definition can declaratively render an already-loaded record/relation as Card, Tag, or Icon.

It must:

- depend only on the shared Record Reference contracts, not Dynamic Table internals;
- accept a trusted Application code, variant, and record/relation source declared server-side;
- reuse the same resolver/access service and shared preview host;
- declare/merge query requirements before rendering;
- never query from the field Blade view;
- expose no complete model in public Livewire state;
- render safe missing/unauthorized states;
- include unit, feature, and query-count tests plus documentation/example usage.

Do not force an artificial Country-to-Country relation merely to demonstrate it. Use an existing real
relation that semantically fits, or a focused test definition if no real current screen is appropriate.

### 9. Keep Dynamic Form status truthful

Confirm again whether a real shared Dynamic Form engine exists.

- If it now exists, add a typed `RecordReferenceField` following its actual conventions and test all
  three variants, scalar hydration/dehydration, authorization, relation changes, validation rerenders,
  lazy preview, and stale-preview invalidation.
- If it still does not exist, do **not** invent a partial/fake form framework as part of this fix.
  Keep `docs/dynamic-form/record-references.md` as an explicit future contract, ensure the shared
  Record Reference API has no Dynamic Table/View coupling that would block it, and do not mark Dynamic
  Form integration complete anywhere.

The final report must clearly distinguish “form-ready shared API” from “implemented Dynamic Form
field.”

### 10. Stabilize tests and correct documentation

Diagnose the full-suite-only failure in `tests/Feature/DynamicTable/FilterOperatorUiTest.php`. Fix the
underlying leaked state, duplicate component registration, database/cache state, static data, or test
isolation issue. Do not weaken or skip the assertion and do not hide the failure with retries.

Correct documentation after code is verified:

- remove the contradictory DONE/PLANNED Record Reference sections in
  `.ai/dynamic-table/CHECKLIST.md` and keep one truthful checklist;
- do not claim hidden-column, browser, authorization, or relation-select coverage unless the tests
  actually exist;
- update `docs/record-references/README.md` with final namespaces, access flow, cache key/version rules,
  real measured query/request budgets, Countries index usage, UI behavior, and limitations;
- update `docs/dynamic-table/record-references.md` and Dynamic Record View docs with working examples;
- keep Dynamic Form documentation explicitly planned if no engine exists;
- preserve `IMPLEMENTATION_PROMPT.md` as historical scope and add a short reference to this remediation
  document rather than rewriting history.

## Mandatory tests

Add or correct tests proving all of the following:

- guest/permission/inactive Application/inactive Country/missing/forged preview denial with no data;
- the same access result for initial render, preview, Countries index, and Country show route;
- unauthorized variants contain no title/facts/link and never `href=""`;
- no queries in Blade and no per-row authorization/Application queries;
- actual Livewire/Blade render query count is constant for 10 versus 50 rows;
- hidden references cause zero metadata/select/eager-load work;
- self and belongs-to Tag/Icon initial SQL omits preview data;
- Card includes only visible Card requirements;
- preview loads only declared fields/relations after action;
- cache/in-flight keys vary by version, locale, and access scope;
- repeat preview reuses the result; changed version/locale/access does not;
- rate guard fails safely;
- valid Card/Tag/Icon HTML, escaping, translations, keyboard/touch behavior, focus, RTL/dark mode, and
  viewport positioning to the extent supported by installed test tooling;
- real `general.world.countries` index route and table behavior;
- Dynamic Record View typed Record Reference integration;
- Dynamic Form tests only if a real engine exists;
- the previously order-dependent FilterOperator UI test is stable in the full suite.

Do not delete existing tests. Correct tests whose names currently contradict their assertions, such as
the “omits the link” test expecting `href=""`.

## Verification sequence

1. Run focused security/access tests.
2. Run Record Reference unit and feature tests.
3. Run Dynamic Table unit and feature tests.
4. Run Dynamic Record View unit and feature tests.
5. Run World Application seeder tests.
6. Run the previously flaky FilterOperator UI file with relevant neighboring suites in both orders.
7. Run `vendor/bin/pint --dirty --format agent` after PHP changes.
8. Run `php artisan test --compact` at least twice from a clean test state. Both runs must pass without
   retries, skips, or order-dependent failures.
9. Build frontend assets if compiled CSS/JS inputs changed.
10. Perform real-browser verification of Card/Tag/Icon interactions where tooling is available and
    check recent browser console logs.

## Definition of done

Do not report this remediation complete until:

- Blade performs zero database queries;
- query counts remain constant through the complete render, not only pagination;
- related Tag/Icon references do not preload preview data;
- all initial/preview/show/index authorization paths agree and fail closed;
- denied references leak no title, URL, facts, or existence signal;
- no unauthorized fallback emits `href=""`;
- cache reuse is correctly scoped and versioned;
- Card/Tag/Icon markup and touch/keyboard behavior are valid and verified;
- `general.world.countries` is a real working Dynamic Table page;
- Dynamic Record View has a typed Record Reference integration;
- Dynamic Form is either genuinely integrated or still labeled planned—never falsely complete;
- documentation/checklists match the delivered code and tests;
- the entire project suite passes twice consecutively;
- the final handoff reports exact changed files, measured query/request counts, test totals, browser
  evidence, remaining limitations, and any consciously deferred work.

