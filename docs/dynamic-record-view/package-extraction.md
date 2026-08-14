# Package Extraction

Status: **Not attempted — assessment only.** No code was moved; this
documents what it would take if the engine were ever pulled out of
`app/Support/DynamicRecordView` into a standalone Composer package.

## Already package-friendly

- **Core** (`app/Support/DynamicRecordView/Core/`) is architecture-tested
  (`tests/Feature/DynamicRecordView/ArchitectureTest.php`) to import nothing
  from `Modules\*`, Livewire, or Blade — it's already framework-agnostic PHP
  plus a hard dependency on Eloquent (`Builder`, `Model`, `Relation`).
- **Resolution** (`app/Support/DynamicRecordView/Resolution/`) depends on
  Core plus Laravel's `DB` facade and HTTP exception — no `Modules\*`
  coupling either.

## What would need to change

1. **Namespace** — `App\Support\DynamicRecordView\*` would become the
   package's own vendor namespace; every consuming `Modules\*` class updates
   its `use` statements.
2. **Service provider** — `RecordResolver`/`RecordReferenceRegistry`/
   `RecordViewRegistry`'s `$this->app->scoped(...)` bindings currently live
   in `App\Providers\AppServiceProvider::register()`; a package needs its own
   `PackageServiceProvider` registering these, auto-discovered via
   `composer.json`'s `extra.laravel.providers`.
2. **Livewire component registration** — `App\Livewire\DynamicRecordView\*`
   components are currently autoloaded by convention; a package needs
   explicit `Livewire::component()` registration in its provider (Livewire
   doesn't autodiscover a package's `Livewire\*` namespace the way an app
   does).
3. **Blade views** — `resources/views/components/dynamic-record-view/` and
   `resources/views/livewire/dynamic-record-view/` would move under the
   package's own `resources/views`, published/loaded via
   `loadViewsFrom()`/`publishes()`, with the `x-dynamic-record-view.*`
   component namespace registered via `Blade::componentNamespace()`.
4. **Flux UI dependency** — the views use `<flux:*>` components directly;
   the package would need to declare `livewire/flux-ui` as a dependency (or
   make the UI layer swappable, which is a larger redesign) rather than
   assuming the host app has it installed, as this codebase does.
5. **Migrations** — none currently belong to this engine directly (it reads
   existing application tables/relations), so no migration-publishing
   concern exists today. If a future feature needed its own table (e.g.
   persisted view preferences specific to record views, distinct from
   `App\Models\TableView`/`UserTablePreference` which already exist and are
   reused as-is), it would need `publishes()`-style migration publishing.
6. **Tests** — `tests/Feature/DynamicRecordView/*` currently exercise real
   `Modules\General\*` models/routes (`SubModule`, `Application`, `Country`,
   `City`) as its canonical examples. A package's own test suite would need
   fixture models/migrations instead, since it can't depend on this app's
   modules.

## Verdict

Core + Resolution are close to package-ready today (clean dependency
boundary, already tested for it). Livewire components, Blade views, and the
Flux UI coupling are the real work — this is a multi-day effort, not
attempted in this pass, and not currently planned.
