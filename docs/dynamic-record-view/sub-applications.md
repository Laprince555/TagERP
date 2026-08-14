# Sub Applications

Status: **Implemented.**

`App\Support\DynamicRecordView\Core\SubApplication` describes one entry in
the Other Data section — one embedded Dynamic Table plus its optional
Link/Unlink actions:

```php
SubApplication::make('applications')
    ->applicationKey('general.sub-module.applications')
    ->label('Applications')
    ->table(ApplicationsTable::class)
    ->relation('applications')      // preferred: named relation on the parent model
    ->authorization(true)           // bool or callable(mixed $record): bool
    ->relationshipActions(
        RelationshipActions::make()
            ->linkExisting(RelationPicker::make()->displayUsing('name')->searchable(['name', 'code']))
            ->linkAuthorization(fn ($user, $parent, $candidate) => $user !== null)
            ->allowReassignment(),
    );
```

`->relation('applications')` names a relation method on the parent model,
resolved fresh per request by `EmbeddedTableContext`. The older
`->forRelation(fn ($record) => $record->applications())` closure form still
works as descriptive metadata but `->relation()` is preferred — it's what
`RelationshipActions`/`RelationPicker` need to resolve the relation without
re-invoking developer closures on every mutation.

`DynamicRecordView::otherDataSection()` turns each `SubApplication` into its
own `RecordTab` — "Other Data lists Sub Applications as tabs" is literal,
each tab *is* one Sub Application — holding a single `TableContent` for that
application's embedded table. If `relationshipActions()` is set,
`otherDataSection()` also calls
`RelationshipActions::assertSupportedFor($this->model(), $relationName)` at
definition time, so an unsupported relation type/shape fails fast when the
view class is built, not on first user interaction.

## Canonical examples

- `Modules\General\System\SubModuleRecordView::subApplications()` — one
  `SubApplication` (`applications`), `HasMany`, Link-only via
  `allowReassignment()` (non-nullable `submodule_id` FK — see
  [relationship-actions.md](relationship-actions.md)).
- `Modules\General\System\World\CountryRecordView` — one `SubApplication`
  (`cities`), same shape, same non-nullable-FK limitation.

See [embedded-tables.md](embedded-tables.md) for how the embedded `Table`
component itself constrains its query to the relation, and
[state-isolation.md](state-isolation.md) for how each embedded instance's
search/filter/page state stays isolated from every other instance of the
same table class.
