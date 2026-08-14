# Sub Applications and Relations

`App\Support\DynamicRecordView\Core\SubApplication` describes one entry in
the Other Data section:

```php
SubApplication::make('applications')
    ->applicationKey('general.sub-module.applications')
    ->label('Applications')
    ->table(SubModuleApplicationsTable::class)
    ->forRelation(fn (SubModule $subModule) => $subModule->applications())
    ->authorization(true); // bool or callable(mixed $record): bool
```

`DynamicRecordView::otherDataSection()` turns each `SubApplication` into its
own `RecordTab` (so "Other Data lists Sub Applications as tabs" is literal —
each tab *is* one Sub Application), holding a single `TableContent` for that
application's embedded table. `->forRelation()` is descriptive metadata
carried onto the `TableContent`; the actual, enforced constraint lives in
the concrete `Table` subclass — see [embedded-tables.md](embedded-tables.md).

## Link/Unlink and the Relation Picker (implemented)

`App\Support\DynamicRecordView\Core\RelationshipActions` and
`App\Support\DynamicRecordView\Core\RelationPicker` are wired end to end:
`SubApplication::relationshipActions()` / `TableContent::relationshipActions()`
enable Link/Unlink on an embedded relation, rendered by
`App\Livewire\DynamicRecordView\RelationPickerModal` and executed
transactionally by `App\Support\DynamicRecordView\Resolution\RelationshipMutator`.
See [relationship-actions.md](relationship-actions.md), [relation-picker.md](relation-picker.md),
and [sub-applications.md](sub-applications.md) for the full mechanism, including
the non-nullable-FK Unlink limitation both canonical examples hit.
