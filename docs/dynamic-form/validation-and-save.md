# Validation & Save

## `DynamicForm` hooks

```php
abstract class DynamicForm
{
    abstract public function model(): string;   // Model::class this form creates
    abstract public function fields(): array;   // Field[]

    public function title(): string;             // default: formKey's last segment, headline-cased
    public function authorize(): bool;            // default: true
    public function create(array $data): Model;   // default: $modelClass::create($data)
    public function validationRules(): array;     // column => rules[], built from fields()
}
```

- **`authorize()`** — an extra, form-level gate beyond the caller's own Application authorization (e.g. a business rule like "can't create a Company while the tax registry sync is running"). `Form::save()` throws `NotFoundHttpException` (a 404, not a 403 — deliberately not revealing the form exists) if this returns `false`. Override it; the default always allows.
- **`create(array $data)`** — receives the already-validated payload, keyed by each field's **persisted column** (via `getColumn()` if the field declares one, else `getKey()`). Override to do more than a bare `Model::create()` — e.g. wrapping in a transaction, setting a computed column, dispatching a domain event.
- **`title()`** — used as the `FormPage` heading and `FormModal` fallback. Override for a real form title (`'Create Company'`) instead of the derived default.

## How `Form::save()` builds and runs validation

1. Calls `$definition->authorize()` — throws `NotFoundHttpException` if `false`.
2. For each `Field` in `fields()`:
   - Calls `$field->validate()` — the field's own config sanity check (throws on misconfiguration, e.g. a `RelationListField` missing `model()`).
   - Resolves its persisted column via `getColumn()`/`getKey()`.
   - Builds `$rules[$column] = $field->getRules()` (`['required'|'nullable', ...customRules]`).
   - Builds `$payload[$column] = $this->data[$field->getKey()] ?? null` — reads the Livewire-bound value out of `data` (keyed by field **key**) into the payload (keyed by persisted **column**).
3. Runs `Validator::make($payload, $rules, [], $customAttributeNames)` — custom attribute names are each field's `getLabel()`, so error messages read `"The Email field is required."` rather than `"The email field is required."` when the label differs from the column.
4. On failure: `$errors_` is set to `$validator->errors()->toArray()` (keyed by column) and nothing is saved. The Blade view looks up `$errors_[$column]` per field to render `<flux:error>` messages.
5. On success: calls `$definition->create($validator->validated())`, dispatches `dynamic-form-saved.{formKey}` with the new record's `id`, and resets `data`, `relationSelected`, and `errors_`.

`DynamicForm::validationRules()` builds the same `column => rules[]` map independently (used for anything that needs the form's rules without going through the full save flow, e.g. a future API endpoint) — it iterates the same fields the same way, so it never drifts from what `Form::save()` actually enforces.

## Adding validation rules

Two levels compose, never conflict:

- **`->required()`** — toggles `required` vs `nullable`, applied first.
- **`->rules([...])`** — anything else: `'email'`, `'max:255'`, `'unique:companies,tax_id'`, `'in:active,inactive'`, `Rule::exists(...)`, etc. Appended after the required/nullable rule.

```php
TextField::make('tax_id')
    ->required()
    ->rules(['max:50', 'unique:companies,tax_id']),
```

There is no field-type-level implicit validation beyond `required`/`nullable` — `TextField::type('email')` and `SelectField::options()` do **not** validate on their own; add `->rules(['email'])` / `->rules(['in:' . implode(',', array_keys($options))])` explicitly when the constraint matters.
