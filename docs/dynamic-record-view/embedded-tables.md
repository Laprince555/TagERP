# Embedding Dynamic Tables

`TableContent::table($tableClass)->relation($name)` (set for you by
`SubApplication::table()->relation()` when building the Other Data section)
names an ordinary `App\Livewire\DynamicTable\Table` subclass and a relation
name on the parent model. The relation constraint is enforced **generically,
by the framework** — `App\Livewire\DynamicTable\Table` plus
`App\Support\DynamicRecordView\Resolution\EmbeddedTableContext` — never by a
bespoke subclass hardcoding a foreign key. **The same Table subclass works
standalone (unconstrained) and embedded any number of times for different
parents (constrained)** — this is the non-negotiable architecture rule for
this engine.

## Why one Table subclass is enough for every embedding

`App\Support\DynamicTable\Query\TableQueryBuilder::query()` always starts
from `($this->definition->query)()` and only ever layers
`select()`/`with()`/`where()`/`whereHas()` on top — it never resets or
replaces the base builder. Search, filters, sort, saved views, and
query-string state all flow through `TableState::normalize()` and then into
those same `where()`/`whereHas()` calls, so none of them can widen the base
query. `Table::definition()` feeds that base builder through
`Table::resolvedQuery()`, which itself layers the relation constraint on top
of the subclass's own `query()` — one more layer in the same never-replaced
chain, applied generically in the base class, not per subclass.

## The pattern (`Modules\General\Livewire\ApplicationsTable`)

```php
class ApplicationsTable extends Table
{
    protected string $tableKey = 'general.applications';
    protected ?string $model = Application::class;

    protected function columns(): array { /* ... */ }
    protected function filters(): array { /* ... */ }
}
```

No `query()` override, no `mount()` override, no knowledge of being
embedded. Used standalone it queries every `Application`. Embedded — e.g. via
`SubModuleRecordView`:

```php
SubApplication::make('applications')
    ->label('Applications')
    ->table(ApplicationsTable::class)
    ->relation('applications'); // SubModule::applications(): HasMany
```

— it's automatically scoped to the current `SubModule`. Two different
`SubModule` records embedding the same `ApplicationsTable` class are two
independent, correctly-scoped instances; neither can see the other's rows.

## How the constraint reaches the table (`EmbeddedTableContext`)

The browser only ever supplies bounded scalars — never a model, builder,
relation, or class name beyond the already-registered `Table` class. Blade's
`x-dynamic-record-view.content` component passes exactly these to the
embedded `@livewire(...)` call:

```php
[
    'embedRecordViewKey' => $recordViewKey,   // RecordViewRegistry key
    'embedRecordId'      => $record->getKey(),
    'embedSection'       => 'primary'|'other-data',
    'embedTab'           => $currentTab->getKey(),
    'embedContent'       => $content->getKey(),
]
```

`Table` carries these as `#[Locked]` public props (immune to client
tampering, survive hydration). `Table::resolvedQuery()` — called fresh from
`definition()` on every request, mount and every subsequent action alike —
passes them to `EmbeddedTableContext::constrain()`, which re-derives
everything from the trusted server-side definition on every single call:

1. Resolve `recordViewKey` through `RecordViewRegistry`.
2. Rebuild the `DynamicRecordView` definition.
3. Resolve the parent through `RecordResolver::resolveFresh()` — a parent
   deleted or made unauthorized between requests fails safely (404) on the
   very next embedded-table interaction, not just at initial mount.
4. Resolve the section (`primarySection()`/`otherDataSection()`), the
   authorized tab, and the `TableContent` by key — never by client-supplied
   index/label.
5. Confirm the content is authorized (`isVisible($parent)`), 404 if not.
6. Confirm the requesting `Table` class exactly matches the class the content
   block declares (`UnknownTableException` otherwise).
7. Resolve the relation from the parent model by name
   (`UnknownRelationException` if it doesn't exist).
8. Confirm it's a supported relation type — `HasMany`, `BelongsToMany`, or
   `MorphMany` (`UnsupportedRelationTypeException` otherwise).
9. Confirm the relation's related model matches the table's own base-query
   model (`TableModelMismatchException` otherwise).
10. Layer the constraint on top of the table's base query via a `whereIn`
    subquery against the relation's own query — never a replacement of the
    base query.

If the content block only declared the legacy `forRelation()` closure (no
inspectable `relation()` name), `constrain()` returns the base query
unconstrained — see "The two relation APIs" below.

## The two relation APIs

- **`TableContent::relation(string $name)` / `SubApplication::relation(string $name)`
  (preferred, canonical)** — a plain, trusted relation name from the PHP
  definition. Introspectable: `EmbeddedTableContext` reads it to resolve the
  actual `Relation` object, and the Link/Unlink mutation engine
  (`RelationshipMutator`) needs exactly this to determine the relation's name
  and type. Use this for anything new.
- **`TableContent::forRelation(Closure)` / `SubApplication::forRelation(Closure)`
  (deprecated, read-only-only)** — a closure `fn ($record) => $record->applications()`.
  Kept only for backward compatibility. **Not equivalent to `relation()`**: a
  closure cannot be introspected to recover a relation name, so
  `EmbeddedTableContext` does not (and structurally cannot) constrain a query
  through it — a content block declaring only `forRelation()` renders its
  table unconstrained, and Link/Unlink will never support it. Migrate to
  `relation()`.

`SubModuleRecordView` (the canonical example) uses `->relation('applications')`.

## Table identity: storage key vs. instance key

`Table` splits identity into two concepts (previously conflated into a single
`instanceKey`/`tableKey()`, which incorrectly created one preference/saved-view
row per parent record):

- **`storageKey()`** — returns `$this->tableKey`, the plain, permanent
  identity subclasses declare as a class property
  (`protected string $tableKey = 'general.applications';`). Used for
  `TablePreferenceStore`/`SavedTableViewStore`. Never varies by parent, so a
  preference set while viewing `ApplicationsTable` embedded under one
  `SubModule` applies the same way standalone or under a different
  `SubModule`.
- **`instanceIdentifier()`** — `$this->instanceKey` (auto-derived from the
  `embed*` props as `"{recordViewKey}:{recordId}:{section}:{tab}:{content}"`)
  when embedded, else falls back to `storageKey()`. Used only for
  query-string parameter namespacing (`queryString()`) and the Livewire
  component's Blade `key(...)`. Guarantees two embedded instances of the same
  table for different parents never share search/filter/sort/page state, and
  never collide in the URL.

Standalone tables never set the `embed*` props, so `instanceKey` stays blank
and `instanceIdentifier()` just returns `storageKey()` — identical behavior
to before this split.

## Per-instance isolation and constant query count

Because `instanceIdentifier()` embeds `recordViewKey:parentId:section:tab:content`,
two embedded instances of the same table class for different parent records
never share search, filters, sort, page, or query-string state — each is a
fully independent Livewire component instance with its own query-string
namespace — while both share the same `storageKey()`-keyed preferences/saved
views. See `tests/Feature/DynamicRecordView/SubModuleRecordViewTest.php` and
`tests/Unit/DynamicTable/*` for the tests proving this (temporary-state
isolation, preference sharing across parents, no per-parent storage row, and
non-colliding query-string keys).

The embedded table's own query cost doesn't grow with the number of related
rows — see `SubModuleRecordViewTest.php`'s `'keeps a constant query count
regardless of related-row count'` test.

## Link/Unlink mutations

Built and wired — see [relationship-actions.md](relationship-actions.md) and
[relation-picker.md](relation-picker.md).

## Second worked example: Country → Cities

`Modules\General\System\World\CountryRecordView` (registered as
`general.world.country`, route `general.world.countries.show`) embeds the
package `Nnjeim\World\Models\Country::cities()` `HasMany` relation via a
"Cities" tab, using a plain, reusable
`Modules\General\Livewire\World\Countries\CitiesTable` (model
`Nnjeim\World\Models\City`) — the same `->relation('cities')` wiring style as
`SubModuleRecordView`'s Applications tab, and, like that table, no
parent-specific `query()` override, so it works fully unconstrained
standalone as well as embedded.

**Link-only, no Unlink — same reasoning as SubModule → Applications.**
`cities.country_id` is `NOT NULL` (see
`vendor/nnjeim/world/src/Database/Migrations/2020_07_07_055725_create_cities_table.php`),
so a City can never be "unassigned" and plain Unlink (null out the FK) is
architecturally impossible — `RelationshipActions::assertSupportedFor()`
would reject `->unlink()` here at definition time. Because no City is ever
unassigned, no City is ever a valid Link candidate by default either,
`allowReassignment()` is what makes Link do anything: it lets a candidate
already linked to a *different* Country be relinked to this one, i.e. moving
a mis-seeded City to its correct Country — a real, useful administrative
correction against a NOT NULL FK, not a fabricated "unassign" capability. See
`CountryRecordView::subApplications()` for the exact configuration and
`tests/Feature/DynamicRecordView/CountryRecordViewTest.php` for the relation
isolation, standalone-reuse, Link/reassignment, and flat-query-count
coverage.
