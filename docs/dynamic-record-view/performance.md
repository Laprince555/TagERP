# Performance

Status: **Implemented.** Findings and fixes from the Phase 10 performance
pass, with real code references.

## Eager loading for dotted `RelationViewField`s

`App\Support\DynamicRecordView\Core\Fields\RelationViewField::make('module.name')`
resolves through `data_get()`, which works whether `module` is already
eager-loaded or triggers a lazy load. Left alone this is N+1-prone: nothing
upstream previously eager-loaded relations named on Basic Information fields.

Fix — `App\Support\DynamicRecordView\Core\DynamicRecordView::requiredEagerLoads()`
walks the view's own `tabs()`, collecting the relation-path prefix (everything
before the last dot) of every `RelationViewField` inside a `FieldsContent`
block:

```php
public function requiredEagerLoads(): array
{
    $relations = [];

    foreach ($this->tabs() as $tab) {
        foreach ($tab->getContents() as $content) {
            if (! $content instanceof FieldsContent) {
                continue;
            }

            foreach ($content->getFields() as $field) {
                if (! $field instanceof RelationViewField || ! str_contains($field->getKey(), '.')) {
                    continue;
                }

                $relations[] = str($field->getKey())->beforeLast('.')->toString();
            }
        }
    }

    return array_values(array_unique($relations));
}
```

`App\Support\DynamicRecordView\Resolution\RecordResolver::resolve()` calls
this before fetching the record and applies it via `->with()`:

```php
$query = $view->query();

if ($eagerLoads = $view->requiredEagerLoads()) {
    $query->with($eagerLoads);
}

$model = $query->find($id);
```

`Modules\General\System\SubModuleRecordView` already declares
`RelationViewField::make('module.name')` on its Basic Information tab, so
`requiredEagerLoads()` returns `['module']` for it — this is the real,
already-existing example the fix targets, not a manufactured one.

## Proof: no lazy loading occurs

`tests/Feature/DynamicRecordView/PerformanceTest.php` enables
`Model::preventLazyLoading()` and renders the real
`general.sub-modules.view` route (whose Basic Information tab includes
`module.name`) — it passes, proving the relation is genuinely eager-loaded
before Blade ever touches `$record->module->name`. Companion assertions
confirm `requiredEagerLoads()` returns `['module']` and that
`RecordResolver::resolve()` produces a model with `relationLoaded('module')`
true and zero extra queries when the relation is subsequently read.

## Relation Picker candidate search

`App\Livewire\DynamicRecordView\RelationPickerModal::candidateQuery()` was
already written defensively before this pass: `select()`s only the primary
key plus search/display fields (never `SELECT *`), takes `limit($take + 1)`
instead of a `COUNT()` to derive `hasMore`, and excludes already-related
candidates via a `whereIn`/`whereNotIn` subquery rather than loading them
to filter in PHP. No changes were needed here — `PerformanceTest.php`'s
"keeps a constant query count for the relation picker candidate search"
test proves the query count opening the picker is identical whether the
candidate table has 5 or 200+ rows.

No new database indexes were added — the candidate query filters by
whichever field the developer declared `searchable()`/relation foreign key,
which already carry standard Eloquent relation/PK indexes in this codebase;
no missing index was found that this application owns (`nnjeim/world`'s own
migrations were left untouched per instructions).
