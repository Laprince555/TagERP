# Request Lifecycle

Status: **Implemented.** One request traced end to end, for
`route('general.sub-modules.view', ['recordId' => $id])`.

## Initial page load

1. The route resolves to the `Modules\General\Livewire\SubModuleRecordView`
   Livewire full-page component (registered in
   `Modules/General/Routes/web.php`).
2. Its `mount(int|string $recordId)` looks up the Core definition
   (`Modules\General\System\SubModuleRecordView`) via
   `RecordViewRegistry::resolve('general.sub-module')` — throws
   `UnknownRecordViewKeyException` if that key was never `register()`-ed.
3. `RecordResolver::resolve($view, $recordId)` runs `$view->requiredEagerLoads()`
   (returns `['module']` here, from the `module.name` field — see
   [performance.md](performance.md)), applies it via `->with()`, and calls
   `$view->query()->find($id)` — 404s via `abort_if()` if not found.
4. `RecordSection::normalizeActiveTabKey(null, $record)` picks the default
   authorized tab (`overview`).
5. Blade renders the Primary section: `RecordSection::authorizedTabs()`,
   then each visible `Content` block, then each visible `Field` inside it —
   `RelationViewField::make('module.name')` reads `$record->module->name`
   with `module` already eager-loaded, no extra query.
6. The Other Data section mounts as a nested, independent Livewire component
   (`App\Livewire\DynamicRecordView\OtherData`), resolving the *same* record
   through its own fresh `RecordResolver::resolve()` call and its own
   `normalizeActiveTabKey()` for the `other-data` section's tabs — its state
   never touches the Primary component's `activeTab`.
7. `OtherData`'s active tab (`applications`) renders a `TableContent`, which
   embeds `Modules\General\Livewire\ApplicationsTable` as a genuine child
   Livewire component, passed only the bounded scalars
   `embedRecordViewKey`/`embedRecordId`/`embedSection`/`embedTab`/`embedContent`
   — never the parent model itself. The embedded table's own `mount()`
   resolves the parent fresh (again) through `EmbeddedTableContext`, checks
   content visibility, resolves the `applications` relation, and constrains
   its own base query with a `whereIn` subquery against that relation.

## A later Livewire action (e.g. switching tabs, Link)

1. Each Livewire action is its own HTTP request with a fresh scoped
   container — `RecordResolver`'s per-request memoization never survives
   between the initial render and a later action, even without the explicit
   `resolveFresh()` call.
2. Action entry points (`setActiveTab`, embedded-table interactions,
   `RelationPickerModal::openPicker()`/`confirmLink()`) call
   `RecordResolver::resolveFresh()` (or `EmbeddedTableContext`, which does
   the same) rather than `resolve()`, so a record deleted or de-authorized
   since mount is caught immediately — converted by Livewire into a plain
   404 response rather than a thrown exception reaching the client.
3. A Link action additionally runs `RelationshipMutator::link()`'s full
   re-resolve -> lock -> re-authorize -> mutate sequence inside one
   `DB::transaction()` — see [security.md](security.md).
