# Dynamic Form Engine

A framework for building create forms declaratively, the same way `App\Support\DynamicTable` builds list pages and `App\Support\DynamicRecordView` builds detail pages. One Livewire component (`App\Livewire\DynamicForm\Form`) renders every field a `DynamicForm` definition declares, validates against each field's own rules, and creates the record — never a hand-built per-Application form.

**Scope**: create-only. There is no edit/update form yet — see [Status](#status).

## Layers

- **Core** (`app/Support/DynamicForm/Core/`) — framework-agnostic. No Livewire, no Blade, no `Modules\*`, no application Eloquent models. `Field` (abstract base), six concrete field types under `Core/Fields/`, `DynamicForm` (abstract definition base), `FormDefinitionRegistry`, `CascadingLevel`.
- **Livewire** (`app/Livewire/DynamicForm/`) — `Form` (all field rendering/validation/save/relation-picker logic), `FormPage` (full-page host), `FormModal` (Flux modal host, wired into `DynamicTable`'s "Create" toolbar button).
- **Blade** (`resources/views/components/dynamic-form/fields/`, `resources/views/livewire/dynamic-form/`) — rendering, Flux Free only.

## Start here

- [quick-start.md](quick-start.md) — define and register a create form in a few minutes.
- [fields.md](fields.md) — every field type, every property, with examples.
- [hosting-and-events.md](hosting-and-events.md) — `FormPage` vs `FormModal`, wiring a `DynamicTable`'s Create button, every dispatched event.
- [validation-and-save.md](validation-and-save.md) — how rules are built, `authorize()`, `create()`, the save flow.
- [testing.md](testing.md) — how to test a form definition.
- [record-references.md](record-references.md) — the still-unbuilt `RecordReferenceField` contract for picking another Application's record.

## Status

Implemented and tested: `TextField`, `TextareaField`, `DateField`, `SelectField`, `RelationListField` (bounded search picker), `CascadingRelationField` (multi-step picker), form-level `authorize()`, validation, `create()`, the modal and full-page hosts, and `DynamicTable` "Create" button wiring.

Not yet implemented:
- **`RecordReferenceField`** (pick another Application's record, reusing `RecordReferenceProvider`) — documented contract only, no implementation. See [record-references.md](record-references.md).
- **Edit/update forms** — `DynamicForm::create()` is the only mutation; there is no update counterpart.

## Canonical example

`Modules\General\System\World\CompanyForm` (registered as `general.world.company.create`), hosted via `Modules\General\Livewire\World\Companies\CompaniesTable::createForm()`. Covers every implemented field type except `SelectField`: `TextField` (plain, `email`, `tel`, `url` variants), `TextareaField`, `RelationListField` (picking a `City`). See `tests/Feature/DynamicForm/CompanyFormTest.php` for full behavioral coverage.
