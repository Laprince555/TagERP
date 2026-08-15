# Quick Start

## 1. Define the form (Core, framework-agnostic)

```php
namespace Modules\General\System\World;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\RelationListField;
use App\Support\DynamicForm\Core\Fields\TextareaField;
use App\Support\DynamicForm\Core\Fields\TextField;
use Modules\General\Models\World\Companies\Company;
use Nnjeim\World\Models\City;

class CompanyForm extends DynamicForm
{
    public function model(): string
    {
        return Company::class;
    }

    public function fields(): array
    {
        return [
            TextField::make('name')->label('Name')->required(),
            TextField::make('tax_id')->label('Tax ID'),
            RelationListField::make('city')
                ->model(City::class)
                ->field('name')
                ->label('City'),
            TextareaField::make('address')->label('Address'),
            TextField::make('phone')->type('tel')->label('Phone'),
            TextField::make('email')->type('email')->label('Email')->rules(['email']),
        ];
    }
}
```

Each field's key (`'name'`, `'tax_id'`, …) is also its persisted column, unless the field type overrides that (`RelationListField`/`CascadingRelationField` default to `{key}_id`, or an explicit `->column()`).

## 2. Register it

In the owning module's `ServiceProvider::boot()`:

```php
use App\Support\DynamicForm\Core\FormDefinitionRegistry;

$this->app->make(FormDefinitionRegistry::class)
    ->register('general.world.company.create', CompanyForm::class);
```

The key (`'general.world.company.create'`) is what routes, the `Form`/`FormPage`/`FormModal` Livewire components, and `DynamicTable::createForm()` all reference. Registering the same key twice throws `InvalidArgumentException`.

## 3. Host it

Cheapest option — wire it to an existing `DynamicTable`'s "Create" button:

```php
// Modules\General\Livewire\World\Companies\CompaniesTable
protected function createForm(): ?string
{
    return 'general.world.company.create';
}
```

That's it — the table now renders a Create button that opens the form in a `FormModal`, and refreshes the table when the record is saved. For a standalone full-page form instead, see [hosting-and-events.md](hosting-and-events.md).

## 4. Done

No Blade, no validation code, no save method to write. `App\Livewire\DynamicForm\Form` renders every declared field via its `component()`, builds validation rules from each field's `getRules()`, and calls `CompanyForm::create()` on success. See [fields.md](fields.md) for the full field API and [validation-and-save.md](validation-and-save.md) for exactly how the save flow works.
