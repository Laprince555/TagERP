# Hosting & Events

Every host renders the same underlying component, `App\Livewire\DynamicForm\Form`, which owns all field rendering, the relation-list picker state, validation, and save. `FormPage` and `FormModal` are thin wrappers around it — there is zero duplication of form logic between the two.

## `App\Livewire\DynamicForm\Form`

Mounted with a single prop: `formKey` (locked — cannot change after mount). Resolves its `DynamicForm` definition via `FormDefinitionRegistry::resolve($formKey)` on every access, so it always reflects the currently-registered definition.

State:
- `data` — `array<string, mixed>`, keyed by each field's `getKey()` (not its persisted column)
- `errors_` — validation messages keyed by **persisted column**
- Relation-picker state (`activeRelationField`, `relationSearch`, `relationResults`, `relationHasMore`, `relationSelected`) — one bounded picker per `RelationListField` on the form, keyed by field key

## `App\Livewire\DynamicForm\FormPage`

Full-page host, `#[Layout('layouts.app')]`. Renders `<livewire:dynamic-form.form>` inside a centered `max-w-3xl` container with the definition's `title()` as the page heading.

```php
Route::middleware(['auth'])
    ->get('/general/world/companies/create', App\Livewire\DynamicForm\FormPage::class)
    ->defaults('formKey', 'general.world.company.create')
    ->name('general.world.companies.create');
```

No concrete route uses `FormPage` in this codebase yet — every current usage goes through `FormModal` (see [Wiring a `DynamicTable`'s Create button](#wiring-a-dynamictables-create-button) below). `FormPage::mount(string $formKey)` accepts `formKey` from any route parameter or `->defaults()` binding, exactly like `mount()` on any other Livewire full-page component.

## `App\Livewire\DynamicForm\FormModal`

A Flux modal (`<flux:modal name="create-{formKey}">`) hosting the same `Form` component. Listens for `dynamic-form-saved.{formKey}` and dispatches `close-form-modal.{formKey}` in response — the modal closes itself the moment the record is saved, no manual wiring needed.

## Wiring a `DynamicTable`'s Create button

Override `createForm(): ?string` on the table (default `null`, which hides the button):

```php
// extends App\Livewire\DynamicTable\Table
protected function createForm(): ?string
{
    return 'general.world.company.create';
}
```

When non-null, the table's toolbar renders a Create button that dispatches `open-form-modal.{createForm()}`, and one `FormModal` is rendered per table (`resources/views/livewire/dynamic-table/table.blade.php`). The table also registers a listener for `dynamic-form-saved.{createForm()}` → `refreshAfterCreate()` (empty hook by default — a handled Livewire listener already re-renders the component, override it only if extra work is needed after a create).

## Events

All events are namespaced by `formKey` (and, for the two relation-picker events, additionally by field key) so multiple forms/pickers on one page never cross-trigger each other.

| Event | Dispatched by | Listened by | Payload |
|---|---|---|---|
| `open-form-modal.{formKey}` | `DynamicTable` toolbar Create button | `FormModal` (Alpine `x-init`) | — |
| `dynamic-form-saved.{formKey}` | `Form::save()` on success | `FormModal::closeAfterSave()`, `DynamicTable::refreshAfterCreate()` | `id: int\|string` (new record's key) |
| `close-form-modal.{formKey}` | `FormModal::closeAfterSave()` | `FormModal` (Alpine `x-init`) | — |
| `relation-list-picker-opened.{formKey}.{fieldKey}` | `Form::openRelationPicker()` | The field's own Blade (Alpine `x-init` opens its `flux:modal`) | — |
| `close-relation-list-picker.{formKey}.{fieldKey}` | `Form::selectRelation()` | The field's own Blade (Alpine `x-init` closes its `flux:modal`) | — |

## Registration

Every `DynamicForm` subclass must be registered exactly once, typically in the owning module's `ServiceProvider::boot()`:

```php
use App\Support\DynamicForm\Core\FormDefinitionRegistry;

$this->app->make(FormDefinitionRegistry::class)
    ->register('general.world.company.create', CompanyForm::class);
```

`FormDefinitionRegistry` is bound `scoped()` in `App\Providers\AppServiceProvider::register()`. Registering a duplicate key throws `InvalidArgumentException`; resolving an unregistered key also throws `InvalidArgumentException` (fail closed, same convention as `RecordViewRegistry` and `RecordReferenceRegistry`).
