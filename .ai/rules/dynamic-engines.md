---
paths:
  - 'app/Livewire/**', 'Modules/**/Livewire/**', 'resources/views/**', 'Modules/**/Resources/views/**'
---

# UI Engines: Dynamic Table, Dynamic Record View & Record References

Always use the project's standardized engines for lists, record details, and relation displays instead of writing ad-hoc Blade views or third-party packages.

## 1. List Pages -> Dynamic Table
- **Class**: Extend `App\Livewire\DynamicTable\Table`.
- **API**: Define `$tableKey`, `$model` (or `query()`), `columns()`, `filters()`, and `defaultSort()` using `App\Support\DynamicTable\Core\*`.
- **Constraint**: Never build raw table HTML, manual pagination, or use third-party table packages (Filament/PowerGrid/etc.).

## 2. Record Detail Pages -> Dynamic Record View
- **Definition**: Extend `App\Support\DynamicRecordView\Core\DynamicRecordView`.
- **API**: Define `$viewKey`, `model()`, `query()`, `title()`, `tabs()`, and `subApplications()`.
- **Host Component**: Use `App\Livewire\DynamicRecordView\RecordView` for rendering.
- **Constraint**: Never build custom monolithic Blade show pages. Embedded child lists in tabs must embed their corresponding `DynamicTable` via `SubApplication` / `TableContent`.

## 3. Relations & Link Cards -> Record References
- **Provider**: Implement `App\Support\RecordReference\RecordReferenceProvider` for the Application model and register it in the module's `ServiceProvider::boot()` via `RecordReferenceRegistry`.
- **In Tables**: Use `RecordReferenceColumn::make('relation_path', 'app-code')`.
- **In Record Views**: Use `RecordReferenceViewField::make('relation_path', 'app-code')`.
- **In Blade**: Render via `<x-record-reference.card>`, `<x-record-reference.tag>`, or `<x-record-reference.icon>`.
- **Constraint**: Never hand-roll custom badges, preview popovers, or relationship links.
