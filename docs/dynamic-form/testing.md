# Testing

Test a `DynamicForm` definition through `App\Livewire\DynamicForm\Form` with `Livewire::test()`, exactly as `CompanyFormTest` does. No dedicated test helpers exist yet — plain Pest + `Livewire::test()` is the whole toolkit.

## Rendering every declared field

```php
it('renders every declared field for the company form', function (): void {
    Livewire::test(Form::class, ['formKey' => 'general.world.company.create'])
        ->assertSee('Name')
        ->assertSee('Tax ID')
        ->assertSee('City')
        ->assertSee('Email');
});
```

## Required-field rejection

```php
it('rejects an empty required field', function (): void {
    Livewire::test(Form::class, ['formKey' => 'general.world.company.create'])
        ->set('data.name', '')
        ->call('save');

    expect(Company::count())->toBe(0);
});
```

## A successful create

```php
it('creates a Company and dispatches the saved event', function (): void {
    Livewire::test(Form::class, ['formKey' => 'general.world.company.create'])
        ->set('data.name', 'Acme Corp')
        ->set('data.email', 'contact@acme.test')
        ->call('save')
        ->assertDispatched('dynamic-form-saved.general.world.company.create');

    $company = Company::where('name', 'Acme Corp')->firstOrFail();
    expect($company->email)->toBe('contact@acme.test');
});
```

Set `data.{key}` (the field's **key**), never `data.{column}` — `RelationListField`/`CascadingRelationField` keys and columns commonly differ (`city` vs `city_id`).

## Exercising a `RelationListField` picker

```php
it('picks a city through the bounded relation-list search and saves its id', function (): void {
    $cairo = City::create([...]);
    City::create([...]); // 'Giza', a second candidate

    Livewire::test(Form::class, ['formKey' => 'general.world.company.create'])
        ->call('openRelationPicker', 'city')
        ->assertSee('Cairo')
        ->assertSee('Giza')
        ->call('selectRelation', 'city', $cairo->id, 'Cairo')
        ->set('data.name', 'Acme Corp')
        ->call('save');

    expect(Company::where('name', 'Acme Corp')->firstOrFail()->city_id)->toBe($cairo->id);
});
```

`openRelationPicker($fieldKey)` populates `relationResults[$fieldKey]` from the field's `model()`/`field()`/`query()` — assert on the resulting labels directly rather than mocking the query. `selectRelation($fieldKey, $id, $label)` is what the Blade's candidate button calls on click; call it directly in tests instead of driving Alpine/Flux modal interactions.

## Testing a `DynamicTable`'s Create wiring

```php
it('shows a Create button on the companies table wired to its form key', function (): void {
    Livewire::test(CompaniesTable::class)
        ->assertSeeHtml('open-form-modal.general.world.company.create');
});
```

Asserts the toolbar renders the exact event name the button dispatches — enough to prove `createForm()` is wired without needing a full browser test of the Alpine/Flux modal interaction.

See `tests/Feature/DynamicForm/CompanyFormTest.php` for the full suite this page is drawn from.
