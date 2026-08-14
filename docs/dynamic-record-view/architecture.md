# Architecture

## Layers

```
Core (framework-agnostic)          App\Support\DynamicRecordView\Core\*
  DynamicRecordView, RecordSection, RecordTab, Content\*, Fields\*,
  SubApplication, RelationshipActions, RelationPicker
  RecordViewRegistry (trusted key -> class lookup)
        |
Resolution                         App\Support\DynamicRecordView\Resolution\{RecordResolver,EmbeddedTableContext,RelationshipMutator}
        |
Livewire adapters                  App\Livewire\DynamicRecordView\{RecordView,OtherData}
        |
Blade                              resources/views/{components,livewire}/dynamic-record-view/*
```

## RecordViewRegistry

Livewire components must never carry a raw `DynamicRecordView` class name as
public state — a malicious client could otherwise instantiate an arbitrary
class. Instead, `RecordView` and `OtherData` carry a `#[Locked] string
$recordViewKey`, resolved to the concrete class only through
`App\Support\DynamicRecordView\Core\RecordViewRegistry::resolve()`, a
server-side trusted key -> class map. Modules register their views from their
own `ServiceProvider::boot()` (see `Modules\General\Providers\GeneralServiceProvider`),
bound as a singleton. An unknown key throws
`Exceptions\UnknownRecordViewKeyException` before anything is instantiated.

Core has zero knowledge of Livewire, Blade, or `Modules\*` — enforced by
`tests/Feature/DynamicRecordView/ArchitectureTest.php`. A `DynamicRecordView`
subclass can be unit tested with plain PHP objects, no HTTP, no Livewire test
harness (see `tests/Unit/DynamicRecordView/CoreTest.php`).

## Primary section vs. Other Data section

`DynamicRecordView::primarySection()` builds a `RecordSection` from `tabs()`.
`DynamicRecordView::otherDataSection()` builds a second, independent
`RecordSection` from `subApplications()` — one `RecordTab` per
`SubApplication`, each holding a `TableContent` for that application's table.

These two sections are rendered by **two separate Livewire components**:
`App\Livewire\DynamicRecordView\RecordView` (primary) and
`App\Livewire\DynamicRecordView\OtherData` (other data), the latter mounted
as a nested Livewire component keyed by the record id. Because they are
genuinely separate component instances:

- switching the primary tab never touches `OtherData`'s state or triggers a
  request for it, and vice versa (Milestone 4);
- an inactive tab's content is never rendered, so an inactive `TableContent`
  never queries (see `other-data.blade.php`'s `@if ($currentTab)` guard);
- keying `OtherData` by `'other-data-'.$viewKey.'-'.$record->getKey()` means
  navigating to a different record (different key) tears down and remounts
  it fresh, while re-rendering the *same* record preserves its state.

## Embedding Dynamic Tables

See [embedded-tables.md](embedded-tables.md) for the full mechanism. In
short: `App\Livewire\DynamicTable\Table::query()` is always the sole source
of a table's own authorized base query, and `TableQueryBuilder::query()` only
ever adds `where()`/`whereHas()` on top of it — it never resets or replaces
the base builder. `Table::resolvedQuery()` layers one more constraint on top
of that chain — the parent relation, resolved generically by
`App\Support\DynamicRecordView\Resolution\EmbeddedTableContext` from bounded
scalar `#[Locked]` props (`embedRecordViewKey`/`embedRecordId`/`embedSection`/
`embedTab`/`embedContent`), never from a bespoke per-relation subclass. The
same `Table` subclass works standalone (unconstrained) and embedded any
number of times for different parents (constrained) — this is enforced
architecture, not convention.

`Table` also splits table identity into a permanent **storage key**
(`storageKey()`, used for preferences/saved views, shared across standalone
and every embedded use) and a temporary **instance key**
(`instanceIdentifier()`, used only for query-string namespacing and the
Livewire component key, unique per embedding). See
[embedded-tables.md](embedded-tables.md#table-identity-storage-key-vs-instance-key).

## Content rendering

`resources/views/components/dynamic-record-view/content.blade.php` is the
single shared renderer for every `Content` subclass in every tab — primary
*and* Other Data alike. It replaces what were previously two separate,
diverging `instanceof` branch sets in `record-view.blade.php` and
`other-data.blade.php`. It enforces: `$content->isVisible($record)`, a stable
`wire:key` per content block, and (for `TableContent`) passes only the
bounded embed-context scalars described above — never the record itself —
to the embedded `Table` component.

## Link/Unlink mutation engine

`App\Support\DynamicRecordView\Core\RelationshipActions` and `RelationPicker`
are real and wired: `TableContent::relationshipActions()` /
`SubApplication::relationshipActions()` enable Link/Unlink UI and
authorization for one embedded relation, executed server-side by
`App\Support\DynamicRecordView\Resolution\RelationshipMutator`. See
[relationship-actions.md](relationship-actions.md) and
[relation-picker.md](relation-picker.md) for the full mechanism.

## Country/Cities canonical example

`Modules\General\System\World\CountryRecordView` and
`Modules\General\Livewire\World\Countries\CitiesTable` are implemented and
tested (see `tests/Feature/DynamicRecordView/CountryRecordViewTest.php`),
reachable at `route('general.world.countries.show', ['recordId' => $country->id])`.
`nnjeim/world`'s `cities` table has a NOT NULL `country_id` foreign key (see
`vendor/nnjeim/world/src/Database/Migrations/2020_07_07_055725_create_cities_table.php`),
so — like `SubModule -> Applications` — it is Link-only via
`allowReassignment()`; ordinary Unlink is architecturally rejected. See
[relationship-actions.md](relationship-actions.md).

## Genuinely unimplemented

- Multi-select in the Relation Picker (single-select only) — see [relation-picker.md](relation-picker.md).
- `RelationshipActions::reassignPolicy()` — a documented throwing stub, no audit/policy object behind it.
- `BelongsToMany` Link/Unlink — the code path exists in `RelationshipMutator` but nothing in this codebase has a real `BelongsToMany` relation to exercise it against.
