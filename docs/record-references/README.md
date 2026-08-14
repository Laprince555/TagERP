# Record References

One shared way to present any Application record — a Country, a Company, an SubModule, etc. — as a
`card`, `tag`, or `icon`. Every variant is driven by the same Application-owned provider; nothing
about title/facts/route/authorization is ever duplicated in a view, a Dynamic Table column, or a
future Dynamic Form field.

## Status

Vertical slice complete for **Country** (`gen-wld-ctr`, backed by `nnjeim/world`'s
`Nnjeim\World\Models\Country`). Only this Application has a registered provider today — see
"Rolling out to another Application" below to add the next one.

## Core namespaces (`app/Support/RecordReference/`)

| Class | Purpose |
| --- | --- |
| `RecordReferenceVariant` | `Card` \| `Tag` \| `Icon` enum |
| `ApplicationColor` | Fixed 9-color palette allowlist (`applications.color` column) |
| `RecordReferencePalette` | Resolves a color token to trusted Tailwind classes — never interpolates |
| `RecordFact` | One escaped label/value/priority triple |
| `RecordReferenceIdentity` | Minimal initial payload (icon, title, url) — zero preview data |
| `RecordReferencePreview` | On-demand facts payload, `available: false` on any denial |
| `RecordReferenceProvider` | Interface each Application implements |
| `RecordReferenceRegistry` | Application code → provider map, populated by module `boot()` |
| `RecordReferenceResolver` | Builds DTOs from an already-loaded record; never queries |

Blade: `resources/views/components/record-reference/{card,tag,icon}.blade.php` (stateless).
Preview host: `app/Livewire/RecordReference/PreviewHost.php` + its Blade view, mounted **once** in
`resources/views/layouts/app.blade.php`.

## Registering a provider

1. Implement `RecordReferenceProvider` inside the owning module (see
   `Modules/General/System/World/CountryRecordReferenceProvider.php`).
2. Register it in that module's `ServiceProvider::boot()`:
   ```php
   $this->app->make(RecordReferenceRegistry::class)->register(new CountryRecordReferenceProvider);
   ```
3. Make sure the Application has a real, authorized, named record route the provider's `url()` can
   call (`route()`, never string concatenation). Country's route
   (`general.world.countries.show`) didn't exist before this feature — see
   `Modules/General/Livewire/World/Countries/CountryRecordView.php`, which reuses the existing
   Dynamic Record View engine (`App\Support\DynamicRecordView`) rather than a bespoke controller.

## Rendering

```blade
<x-record-reference.card :identity="$identity" :facts="$facts" />
<x-record-reference.tag :identity="$identity" />
<x-record-reference.icon :identity="$identity" />
```

Build `$identity`/`$facts` via `RecordReferenceResolver::identity()` / `::cardFacts()` from a
record you already loaded — never inside the Blade component.

## Lazy preview lifecycle

- **Card**: all facts are fetched in the same bounded query that loaded the visible rows. No
  secondary request, ever.
- **Tag**: identity only on initial render. Right-click, `Shift+F10`/Context-Menu key, or the
  touch "..." button dispatches a `record-reference:open-preview` window `CustomEvent`.
- **Icon**: identity only on initial render. Focus, touch, or a 400 ms stable hover dispatches the
  same event (hover is cancelled via `clearTimeout` if the pointer leaves first).
- The single `PreviewHost` Livewire component listens for that event, resolves the provider via
  the server registry, authorizes, and queries **only** `identityColumns() + previewColumns()`.
- The client (Alpine, inline in `preview-host.blade.php`) keeps a per-page `cache` object keyed by
  `code:key` and an `inflight` map to dedupe concurrent triggers. Reopening the same
  record in the same page load never issues a second Livewire call.
- Cross-request fact caching is off by default (`provider->cacheTtl(): null`). Application metadata
  is memoized per Dynamic Table render (one query for every distinct Application code referenced,
  not one per row) — no dedicated cross-request cache layer was added; wiring into the existing
  navigation cache is a follow-up (see Limitations).

## Security boundaries

- Client input to `PreviewHost::loadPreview()` is two bounded scalar strings
  (`applicationCode`, `recordKey`) — never a class, route, column, or SQL fragment.
- `PreviewHost` always resolves through `RecordReferenceRegistry` — a forged/unknown code returns
  the same generic `{available: false}` shape as an unauthorized or missing record. No existence
  leak.
- `authorize()` is provider-owned and re-checked on every call; nothing is cached across users.
- Facts are plain scalars rendered through Blade's `{{ }}` escaping — a hostile title/fact value
  renders as literal text, never HTML (see `RecordReferenceBladeVariantsTest`).
- `RecordReferenceColumn::getLink()`/`Application::url()` only ever produce `route()`-generated
  URLs.

## Measured budgets (see test evidence in the three doc files + PR report)

- Icon/tag Dynamic Table render: 1 count query + 1 data query, 0 preview queries, constant
  regardless of row count (`DynamicTableRecordReferenceColumnTest`).
- Card render: identity + declared card columns only, in the same select — 0 secondary queries.
- First preview: 1 primary record query (0 relation queries for Country since it has none
  declared).
- Repeat preview open in the same page load: 0 requests (client cache; not exercised by Pest,
  which cannot assert "no network request" — see Limitations).

## Known limitations / deviations

- Only Country has a provider. Rolling out State/City/Timezone/Currency/Language/Company/Person
  is explicitly out of scope for this slice (per the original instruction to prove the vertical
  slice first).
- Application metadata memoization is request-scoped only (per Dynamic Table render / per
  `PreviewHost` call), not wired into a cross-request cache tag. `RecordReferenceRegistry` is
  bound `scoped()` for the same reason — safe default, not a performance ceiling.
- The "zero HTTP request on reopen" guarantee lives entirely in the Alpine client cache in
  `preview-host.blade.php`; there is no Pest browser test proving it (no Pest browser testing
  package is configured in this project — confirmed before writing tests). It is covered by
  reading the client code + unit-level `PreviewHost::loadPreview()` correctness instead.
- Popover collision-aware repositioning is viewport-collision-aware: it implements horizontal clamping, vertical flipping when exceeding the viewport height, and scroll/resize repositioning.
