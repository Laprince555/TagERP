# Quick Start

## 1. Define columns, filters, and a query as a Livewire component

Every table is one Livewire component extending `App\Livewire\DynamicTable\Table`. There is no
separate "table class" — the abstract methods live directly on the component.

```php
<?php

namespace App\Livewire\DynamicTable;

use App\Models\User;
use App\Support\DynamicTable\Core\Columns\BooleanColumn;
use App\Support\DynamicTable\Core\Columns\DateTimeColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\DateFilter;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use Illuminate\Database\Eloquent\Builder;

class CustomersTable extends Table
{
    // Required: stable, explicit, independent of the class name. Namespaces
    // preferences, saved views, and query-string state for this table.
    protected string $tableKey = 'general.customers';

    // Gives the table a safe default query (`User::query()`) with no need to
    // override query() at all — see "Model-based default query" below.
    protected ?string $model = User::class;

    protected function columns(): array
    {
        return [
            TextColumn::make('name')->sortable()->searchable()->label('Name'),
            TextColumn::make('email')->sortable()->searchable()->label('Email'),
            BooleanColumn::make('is_active')->sortable()->label('Active'),
            DateTimeColumn::make('created_at')->sortable()->label('Joined'),
        ];
    }

    protected function filters(): array
    {
        return [
            TextFilter::make('name'),
            DateFilter::make('created_at'),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('created_at')->descending()];
    }
}
```

## Model-based default query vs. overriding query()

Two ways to give a table its base Eloquent query:

**Set `$model`** (shown above) — the base `Table::query()` returns `($this->model)::query()`
automatically. Throws `InvalidModelException` at render time if `$model` isn't set to a real
`Illuminate\Database\Eloquent\Model` subclass and `query()` also wasn't overridden — never a
silent empty result set.

**Override `query()`** — for scoping (tenant, permissions, soft-delete visibility), call
`parent::query()` to reuse the model-based default and add to it:

```php
protected ?string $model = Customer::class;

protected function query(): Builder
{
    return parent::query()->where('company_id', auth()->user()->company_id);
}
```

Or skip `$model` entirely and build the query from scratch:

```php
protected function query(): Builder
{
    return Customer::query()->visibleTo(auth()->user());
}
```

A table is never forced to implement `query()` just to use a plain, unscoped model query.

## 2. Render it

```blade
<livewire:dynamic-table.customers-table />
```

The base `Table::render()` compiles the current request's state into a query via
`TableQueryBuilder`, paginates it, and renders `resources/views/livewire/dynamic-table/table.blade.php`,
which composes the Flux-based partials (toolbar, search, filter panel, column manager, table body,
pagination).

## 3. What you get for free

- Global search across every `->searchable()` column (submit-triggered, see [search.md](search.md)).
- Deferred filters with an explicit Apply action (one Livewire request for N filter changes).
- Sortable columns, clickable headers, stable primary-key tie-breaking.
- A column-visibility/order manager, persisted per user (see [preferences.md](preferences.md)).
- Personal saved views (see [saved-views.md](saved-views.md)).
- Pagination with an allow-listed per-page selector.

## Minimal working example (tested)

The canonical, test-verified example is `App\Support\DynamicTable\Examples\DemoTable` rendered
via `App\Livewire\DynamicTable\DemoTableComponent`:

```php
class DemoTableComponent extends Table
{
    protected string $tableKey = 'demo-users';

    protected function columns(): array { return DemoTable::columns(); }
    protected function filters(): array { return DemoTable::filters(); }
    protected function query(): Builder { return DemoTable::query(); }
    protected function defaultSort(): array { return DemoTable::defaultSort(); }
}
```

```blade
<livewire:dynamic-table.demo-table-component />
```

See `tests/Feature/DynamicTable/LivewireTableTest.php` for the full set of interactions this
example is verified against.
