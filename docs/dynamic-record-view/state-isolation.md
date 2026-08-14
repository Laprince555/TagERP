# State Isolation: Stable vs. Instance Keys

Status: **Implemented.** This is a key architectural point carried over from
`App\Support\DynamicTable` into embedded tables and applies equally to
Dynamic Record View's own tab state.

## The problem

The same `Table` subclass (e.g. `Modules\General\Livewire\ApplicationsTable`)
can appear multiple times on one page — once per embedded instance, one per
parent record — and can also be visited standalone. Two concerns must never
collide:

- **Permanent preferences** (column visibility/order, saved views) should be
  shared across every instance of the same logical table definition.
- **Temporary per-visit state** (search term, filters, current page, sort)
  must stay isolated per embedded instance, so switching the "Other Data" tab
  from a different parent record never leaks another record's search term or
  page number into this one.

## The two methods (`App\Livewire\DynamicTable\Table`)

```php
// Permanent storage identity — TablePreferenceStore / SavedTableViewStore.
// Stable for the logical table definition; NEVER includes a parent record
// id, so standalone and every embedded instance of the same table class
// share the same preferences/saved views.
protected function storageKey(): string
{
    return $this->tableKey;
}

// Temporary instance identity — query-string namespacing, DOM keys. Falls
// back to storageKey() when the table isn't embedded, so a standalone
// table keeps its original single-key behavior unchanged.
protected function instanceIdentifier(): string
{
    return $this->instanceKey !== '' ? $this->instanceKey : $this->storageKey();
}
```

`$tableKey` is the table's own fixed identity (set by the concrete `Table`
subclass). `$instanceKey` is set only when the table is mounted embedded
(see `embedRecordViewKey`/`embedRecordId`/`embedSection`/`embedTab`/
`embedContent` props passed by `resources/views/components/dynamic-record-
view/content.blade.php`), and is derived from those bounded scalars — never
the record itself — so it's unique per (record view, parent id, section,
tab, content key) tuple.

`App\Livewire\DynamicRecordView\RelationPickerModal` follows the identical
pattern for its own `instanceIdentifier()`, keying its Livewire event
listeners (`open-relation-picker.{id}`) and modal name per embedded content
block so two Relation Pickers on the same page never cross-trigger.

## What this guarantees

- Column visibility/order and saved views set on one embedded instance apply
  to every other instance of the same table class, including the standalone
  page — they're a property of the *table definition*, not the visit.
- Search/filter/sort/page state set while viewing SubModule A's Applications
  tab never appears when viewing SubModule B's Applications tab, even though
  both mount the exact same `ApplicationsTable` class.
- Covered by `tests/Feature/DynamicRecordView/EmbeddedTableIdentityTest.php`
  and the "two embedded instances... do not share temporary state" /
  "keeps a constant query count" tests in
  `tests/Feature/DynamicRecordView/SubModuleRecordViewTest.php`.
