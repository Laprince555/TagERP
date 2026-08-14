# Dynamic Table: Record Reference Column

`App\Support\DynamicTable\Core\Columns\RecordReferenceColumn` renders a record reference
(card/tag/icon) as a table cell. It never returns HTML from `formatUsing()` — it's a typed column
the renderer (`resources/views/components/dynamic-table/table.blade.php`) dispatches on.

```php
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\RecordReference\RecordReferenceVariant;

RecordReferenceColumn::make('reference')
    ->applicationCode('gen-wld-ctr')
    ->variant(RecordReferenceVariant::Icon);

// Referencing a belongsTo relation instead of the row itself:
RecordReferenceColumn::make('country')
    ->applicationCode('gen-wld-ctr')
    ->variant(RecordReferenceVariant::Tag)
    ->relation('country'); // dotted path already declared on other visible RelationColumns
```

`applicationCode()` and `relation()` are trusted, developer-declared config — never client input.
`field()`/`sortable()`/`searchable()` still back a real scalar column when you want the reference
column sortable/searchable by an underlying value (e.g. the record's name); the reference itself
doesn't invent a virtual field.

## Query engine integration (`TableQueryBuilder`)

- **Select**: for a self-referencing column (no `relation()`), only the provider's
  `identityColumns()` are selected for `Tag`/`Icon`; `cardColumns()` are added too when the
  visible variant is `Card`. Preview-only columns are never selected here.
- **Relation columns**: `relation()` reuses the same eager-load/FK-select path as
  `RelationColumn` — the relation is `with()`-loaded once, not queried per row.
- **Duplicate requirements are merged**: multiple visible columns needing the same relation path
  collapse into one `with()` call (`array_unique`).
- One extra query total per Dynamic Table render for Application metadata (all distinct
  `applicationCode()`s referenced by the definition, fetched once in
  `resources/views/components/dynamic-table/table.blade.php`, not once per row/column).

## Rendering (`table.blade.php`)

The row/cell loop branches on `$column instanceof RecordReferenceColumn` and renders
`<x-record-reference.card|tag|icon>` through `RecordReferenceResolver`, built from the
already-loaded `Application` + record — no query in Blade. `wire:key` on the cell is unchanged
(`cell-{rowId}-{columnKey}`), so reordering/toggling still keeps DOM identity stable.

## Visible/hidden column tests

`tests/Feature/RecordReference/DynamicTableRecordReferenceColumnTest.php` proves:

- the `Icon`/`Tag` variant's SQL never selects a preview-only column;
- the `Card` variant's SQL includes the declared card columns;
- the query count for the first page (`paginate()`) is identical whether the table has 10 rows or
  60 rows — no query grows with row count.

## Column manager / preferences / saved views

Unchanged. `RecordReferenceColumn` is a normal `Column` subclass, so toggling, reordering, and
`TablePreferenceStore`/`SavedTableViewStore` treat it like any other column — no special-casing
was needed there.

## Checklist

See `.ai/dynamic-table/CHECKLIST.md` — Record Reference column support is recorded as an addendum
under Milestone 3, not a new milestone (the engine itself didn't change shape, one column type and
one renderer branch were added).

## Deviations / follow-ups

- Only the self-reference and single-level `relation()` cases are implemented. A dotted
  multi-level relation path (`company.country`) is not supported by `RecordReferenceColumn` today
  — extend `getRecord()`/the query builder branch if a future table needs it.
- Export (`exportable()`) still only flips the config flag; no exporter exists yet in the base
  Dynamic Table engine, so this column doesn't add raw-export behavior beyond that existing flag.
