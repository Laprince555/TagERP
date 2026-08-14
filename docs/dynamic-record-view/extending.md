# Extending the Engine

Status: **Implemented** (these are the real, exercised extension points).

## Add a new field type

Extend `App\Support\DynamicRecordView\Core\Fields\Field` (see
`TextViewField`, `MoneyViewField`, `RelationViewField` for the shape).
Override `resolveValue()` only if the field needs non-default value
resolution (dotted relation paths, computed values); override
`formattedValue()` for type-specific default display formatting that
`formatUsing()` should still be able to override. Then teach
`resources/views/components/dynamic-record-view/fields-content.blade.php`
how to render it if it needs special markup beyond the generic
value/badge/link branches already there (e.g. `LinkViewField`'s
`method_exists($field, 'getUrl')` branch).

If the new field type resolves a relation path (like `RelationViewField`),
also update `DynamicRecordView::requiredEagerLoads()` if it should
contribute to eager-loading — currently that method only recognizes
`RelationViewField` specifically.

## Add a new content block type

Extend `App\Support\DynamicRecordView\Core\Content\Content` (see
`FieldsContent`, `TableContent`, `EmptyStateContent`,
`SubApplicationContent`). Register its rendering in
`resources/views/components/dynamic-record-view/content.blade.php`'s
`@if`/`@elseif` chain, keyed on `instanceof`.

## Add a new record view

1. Create a Core definition extending `DynamicRecordView` in the owning
   module's `System/` directory (see `Modules\General\System\SubModuleRecordView`,
   `Modules\General\System\World\CountryRecordView`) — implement `model()`,
   `query()`, `title()`, optionally `subtitle()`, `tabs()`, `subApplications()`.
2. Register it: `RecordViewRegistry::register($key, static::class)` — see
   `Modules\General\Providers\GeneralServiceProvider` for where the existing
   two are registered.
3. Create the module's own Livewire full-page wrapper (see
   `Modules\General\Livewire\SubModuleRecordView`) and route it in the
   module's `Routes/web.php`.
4. If it embeds a relation, create/reuse a `Table` subclass for it (see
   [embedded-tables.md](embedded-tables.md)) and, if Link/Unlink is wanted,
   a `RelationshipActions`/`RelationPicker` pair (see
   [relationship-actions.md](relationship-actions.md)).

## Add Link/Unlink to a new relation

Call `->relationshipActions(RelationshipActions::make()->linkExisting(...))`
(and `->unlinkable()` if the FK is nullable) on the `SubApplication` or
`TableContent`. `RelationshipActions::assertSupportedFor()` runs at
definition time and will throw immediately if the relation type/shape isn't
supported (`HasMany`, `BelongsToMany`, `MorphMany` only) or if Unlink is
requested against a non-nullable FK — see
[relationship-actions.md](relationship-actions.md) and
[troubleshooting.md](troubleshooting.md).
