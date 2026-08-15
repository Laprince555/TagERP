# Fields

All field types extend `App\Support\DynamicForm\Core\Field` and live in
`App\Support\DynamicForm\Core\Fields`:

`TextField`, `TextareaField`, `DateField`, `SelectField`, `RelationListField`, `CascadingRelationField`.

Every field is created with `SomeField::make('key')` and configured through a fluent chain that returns `static`, so calls can be chained in any order. `'key'` is both the field's identity within the form (`data.{key}` in Livewire, event names, error lookups) and — unless the field type says otherwise — its persisted column name.

## Shared API (`Field`)

Available on every field type:

| Method | Default | Effect |
|---|---|---|
| `->label(string $label)` | headline-cased key, e.g. `tax_id` → `Tax Id` | Field label text |
| `->required(bool $required = true)` | `false` | Adds Laravel's `required` rule (else `nullable`) |
| `->placeholder(string $placeholder)` | `null` | Placeholder text / picker button text |
| `->helpText(string $text)` | `null` | Rendered as `<flux:description>` below the input |
| `->rules(array $rules)` | `[]` | Extra Laravel validation rules, appended after `required`/`nullable` |

Getters (used by the rendering layer, rarely called directly): `getKey()`, `getLabel()`, `isRequired()`, `getPlaceholder()`, `getHelpText()`, `getRules()` (returns the full `['required'|'nullable', ...$rules]` array).

```php
TextField::make('email')
    ->label('Email')
    ->required()
    ->placeholder('you@example.com')
    ->helpText('Used for invoices.')
    ->rules(['email', 'max:255']);
```

Every field type may also override `validate(): void` — a final, order-independent sanity check run once per field after its whole fluent chain has executed (e.g. `RelationListField` throws if `model()`/`field()` were never called). This runs whenever `DynamicForm::validationRules()` or `Form::save()` iterates the fields — not at `make()` time — so a misconfigured field only surfaces when the form is actually used.

---

## TextField

`component()`: `dynamic-form.fields.text` — renders `<flux:input type="{type}">`.

| Method | Default | Effect |
|---|---|---|
| `->type(string $type)` | `'text'` | HTML input type: `'text'`, `'email'`, `'tel'`, `'url'`, `'password'` |

```php
TextField::make('name')->label('Name')->required(),
TextField::make('email')->type('email')->rules(['email']),
TextField::make('phone')->type('tel'),
TextField::make('website')->type('url'),
```

`type()` only changes the HTML `type` attribute (browser-level keyboard/format hints) — it does **not** add validation. Pair `type('email')` with `->rules(['email'])` if the value must actually be a valid email.

## TextareaField

`component()`: `dynamic-form.fields.textarea` — renders `<flux:textarea>` (via a multi-row `<flux:input>` role in the current Blade).

| Method | Default | Effect |
|---|---|---|
| `->rowsCount(int $rows)` | `3` (min `1`) | Visible row count |

```php
TextareaField::make('address')->label('Address')->rowsCount(4),
```

## DateField

`component()`: `dynamic-form.fields.date` — renders `<flux:input type="date">`.

No field-specific options — only the shared `Field` API (label, required, placeholder, helpText, rules). Add format/range constraints via `->rules(['date', 'after:today'])` etc.

```php
DateField::make('hired_at')->label('Hire Date')->required()->rules(['date']),
```

## SelectField

`component()`: `dynamic-form.fields.select` — renders `<flux:select>` with an always-present empty "Select…" option first.

| Method | Default | Effect |
|---|---|---|
| `->options(array $options)` | `[]` | `value => label` map rendered as `<flux:select.option>` |

```php
SelectField::make('status')
    ->label('Status')
    ->options([
        'active' => 'Active',
        'inactive' => 'Inactive',
    ])
    ->required(),
```

`SelectField` does not validate that the submitted value is one of `options()` — add `->rules(['in:active,inactive'])` (or `Rule::in(array_keys($options))`) if that must be enforced.

## RelationListField

`component()`: `dynamic-form.fields.relation-list` — a bounded, searchable picker for a `belongsTo` value. Renders as a button that opens a modal with a debounced search box, the first `pageSize()` matches, and a "Load more" button up to `maximumLoadedResults()`. **Never** a plain `<select>` loading every row — use this for any relation, however small the table looks today.

| Method | Default | Effect |
|---|---|---|
| `->model(string $model)` | — (**required**) | The related Eloquent model class |
| `->field(string $field)` | — (**required**) | Column used for display; also the default search field |
| `->searchable(array $fields)` | `[$field]` | Override which columns are searched (still displays `field()`) |
| `->column(string $column)` | `{key}_id` | Destination column on the form's model for the picked row's key |
| `->pageSize(int $size)` | `5` | Rows fetched per page/search |
| `->maximumLoadedResults(int $max)` | `50` | Hard cap on rows accumulated client-side across "Load more" clicks |
| `->query(Closure $callback)` | `null` | `fn (Builder $query): Builder` — scope/filter the candidate query (e.g. restrict by tenant, status). Applied to the picker **and** to the `exists` validation rule, so a crafted request can't submit an id outside the scope; use only constraints a plain query builder understands (no model scopes or relation methods) |

```php
RelationListField::make('city')
    ->model(City::class)
    ->field('name')
    ->label('City'),

// searching by two columns, displaying only one, scoped, custom column, larger cap
RelationListField::make('manager')
    ->model(Employee::class)
    ->field('full_name')
    ->searchable(['full_name', 'email'])
    ->column('manager_employee_id')
    ->pageSize(10)
    ->maximumLoadedResults(100)
    ->query(fn (Builder $q) => $q->where('is_active', true)),
```

`->validate()` throws `InvalidArgumentException` if `model()` or `field()` were never called — a `RelationListField` is unusable without both.

The picked value is written to `data.{key}` in the Livewire component and saved under `getColumn()` (`{key}_id` by default) — the field's own `key` is never itself a column when it's a relation picker.

## CascadingRelationField

`component()`: `dynamic-form.fields.cascading-relation` — a multi-step picker (e.g. Country → State → City), each level locked until the previous is chosen and filtered by it. The picker state and queries live in `App\Livewire\DynamicForm\Form` (`openCascadePicker`/`chooseCascade`/`loadMoreCascade`, state keyed `[fieldKey][levelKey]`); the panel is rendered inline by `resources/views/components/dynamic-form/fields/cascading-relation.blade.php` rather than in a nested modal. See `tests/Feature/DynamicForm/CascadingCityPickerTest.php`.

| Method | Default | Effect |
|---|---|---|
| `->level(CascadingLevel $level)` | — | Append one step; call once per level, in order |
| `->column(string $column)` | `{key}_id` | Destination column for the **last** level's picked id — earlier levels only narrow the search, nothing else about them is persisted |

`validate()` throws if fewer than 2 levels are declared, or if any level after the first has no `dependsOn()`.

### CascadingLevel

One step of a `CascadingRelationField`, from `App\Support\DynamicForm\Core\CascadingLevel`:

| Method | Default | Effect |
|---|---|---|
| `CascadingLevel::make(string $key, string $model)` | — | Constructor: this level's key + Eloquent model class |
| `->field(string $field)` | `null` | Display field for this level's options |
| `->dependsOn(string $levelKey)` | `null` | Previous level's key this one is filtered by (required for every level after the first) |
| `->foreignKey(string $column)` | `{dependsOn}_id` | Column on **this** level's model pointing at the parent level's id |
| `->pageSize(int $size)` | `5` | Same semantics as `RelationListField::pageSize()` |
| `->maximumLoadedResults(int $max)` | `50` | Same semantics as `RelationListField::maximumLoadedResults()` |

```php
CascadingRelationField::make('city')
    ->level(CascadingLevel::make('country', Country::class)->field('name'))
    ->level(
        CascadingLevel::make('state', State::class)
            ->field('name')
            ->dependsOn('country')
    )
    ->level(
        CascadingLevel::make('city', City::class)
            ->field('name')
            ->dependsOn('state')
            ->foreignKey('state_id')
    )
    ->column('city_id'),
```

Use `RelationListField` for any single-level relation pick; reach for the cascade only when one level alone can't bound the candidate list.
