# Dynamic Record View Engine

A framework for building record detail ("show") pages declaratively, the same way `App\Support\DynamicTable` builds list pages. This is the final, closed-out state of the engine: core definitions, authorized record resolution, the Primary section (fields/tabs), an independent Other Data section, read-only embedding of existing Dynamic Tables, Link/Unlink relationship mutation, the searchable Relation Picker, and security/eager-loading hardening are all implemented and tested.

The only genuinely unfinished pieces are: a multi-select Relation Picker, a real `reassignPolicy()` hook (currently a throwing stub), and a `BelongsToMany` Link/Unlink path exercised against a real `BelongsToMany` relation (the code path is written but nothing in this codebase uses `BelongsToMany` to test it against). See [relationship-actions.md](relationship-actions.md) and [relation-picker.md](relation-picker.md) for exact status.

## Layers

- **Core** (`app/Support/DynamicRecordView/Core/`) — framework-agnostic. No Livewire, no Blade, no `Modules\*`, no application Eloquent models. Architecture-tested in `tests/Feature/DynamicRecordView/ArchitectureTest.php`.
- **Resolution** (`app/Support/DynamicRecordView/Resolution/`) — `RecordResolver` resolves a record only through its view's authorized `query()`, with required relations eager-loaded; `RelationshipMutator` performs Link/Unlink transactionally; `EmbeddedTableContext` resolves an embedded table's parent/content.
- **Livewire** (`app/Livewire/DynamicRecordView/`) — `RecordView` (Primary section), `OtherData` (independent Other Data section), `RelationPickerModal` (Link candidate search + confirm).
- **Blade** (`resources/views/components/dynamic-record-view/`, `resources/views/livewire/dynamic-record-view/`) — rendering, Flux Free only.

## Start here

- [quick-start.md](quick-start.md) — build a record view in a few minutes.
- [architecture.md](architecture.md) — how the layers fit together and why.
- [defining-record-views.md](defining-record-views.md), [sections-tabs.md](sections-tabs.md), [content-blocks.md](content-blocks.md), [fields.md](fields.md) — the Core API.
- [record-resolution.md](record-resolution.md) — authorization and 404 behavior.
- [embedded-tables.md](embedded-tables.md), [relations.md](relations.md), [sub-applications.md](sub-applications.md) — embedding existing Dynamic Tables and declaring Sub Applications.
- [relationship-actions.md](relationship-actions.md), [relation-picker.md](relation-picker.md) — Link/Unlink and the candidate picker.
- [state-isolation.md](state-isolation.md) — stable vs. instance keys for embedded/tab state.
- [security.md](security.md) — URL allowlisting, fail-closed registry, transactional mutation, state normalization.
- [performance.md](performance.md) — eager-loading and query-count guarantees.
- [authorization.md](authorization.md) — every authorization hook in the engine.
- [request-lifecycle.md](request-lifecycle.md) — a request traced end to end.
- [accessibility.md](accessibility.md) — current a11y state, honestly.
- [extending.md](extending.md) — adding a new field type, content block, or record view.
- [troubleshooting.md](troubleshooting.md) — common errors and fixes.
- [package-extraction.md](package-extraction.md) — what it would take to extract this into a standalone package.
- [testing.md](testing.md) — how the existing test suite is organized.

## Canonical examples

- **SubModule -> Applications** (`hasMany`, non-nullable FK): `Modules\General\System\SubModuleRecordView` (Core definition) + `Modules\General\Livewire\SubModuleRecordView` (Livewire page), reachable at `route('general.sub-modules.view', ['recordId' => $subModule->id])`. Its Other Data section embeds the real `SubModule::applications()` relation via `Modules\General\Livewire\ApplicationsTable`, with Link-only relationship actions (see [relationship-actions.md](relationship-actions.md)).
- **Country -> Cities** (`hasMany`, non-nullable FK, geographic/`nnjeim/world` data): `Modules\General\System\World\CountryRecordView`, reachable at `route('general.world.countries.show', ['recordId' => $country->id])`. Its Other Data section embeds `Modules\General\Livewire\World\Countries\CitiesTable`, also Link-only for the same non-nullable-FK reason.
