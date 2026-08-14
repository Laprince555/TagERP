# Testing Custom Tables

All examples below are taken directly from this engine's own test suite
(`tests/Unit/DynamicTable/`, `tests/Feature/DynamicTable/`) — run them yourself:

```bash
php artisan test --compact --filter=DynamicTable
```

## Testing a Livewire table component

```php
use App\Livewire\DynamicTable\DemoTableComponent;
use App\Models\User;
use Livewire\Livewire;

test('renders populated state with users', function () {
    User::factory()->create(['name' => 'Zed Example']);

    Livewire::test(DemoTableComponent::class)
        ->assertSee('Zed Example');
});
```

## Testing search

```php
test('search is submit triggered and resets the page', function () {
    User::factory()->create(['name' => 'Findable Name']);
    User::factory()->create(['name' => 'Other Person']);

    Livewire::test(DemoTableComponent::class)
        ->set('page', 2)
        ->set('search', 'Findable')
        ->call('submitSearch')
        ->assertSet('page', 1)
        ->assertSee('Findable Name')
        ->assertDontSee('Other Person');
});
```

## Testing filters (deferred apply)

```php
test('filter draft then apply costs one request', function () {
    User::factory()->create(['name' => 'Apply Target']);

    Livewire::test(DemoTableComponent::class)
        ->set('filters.name.operator', 'equals')
        ->set('filters.name.value', 'Apply Target')
        ->assertSet('appliedFilters', [])   // draft not applied yet
        ->call('applyFilters')
        ->assertSet('appliedFilters', ['name' => ['operator' => 'equals', 'value' => 'Apply Target']])
        ->assertSee('Apply Target');
});
```

## Testing sorting

```php
test('sorting toggles direction and resets the page', function () {
    Livewire::test(DemoTableComponent::class)
        ->set('page', 2)
        ->call('sortBy', 'name')
        ->assertSet('sorts', [['column' => 'name', 'direction' => 'desc']])
        ->assertSet('page', 1);
});
```

## Testing authorization (column visibility per user)

```php
test('a column with visible(false) is excluded from sql select', function () {
    $definition = new TableDefinition(
        tableKey: 'x',
        columns: [
            TextColumn::make('title'),
            TextColumn::make('salary')->visible(fn () => auth()->user()?->can('view-salary') ?? false),
        ],
        filters: [],
        query: fn () => Employee::query(),
    );

    $state = TableState::normalize([], $definition); // no user authenticated
    expect($state->visibleColumns)->not->toContain('salary');
});
```

For a full adversarial pass (tampered state, forced unauthorized keys), see
`tests/Feature/DynamicTable/SecurityHardeningTest.php`.

## Testing query engine directly (no Livewire)

Working against `TableQueryBuilder`/`TableState` directly is faster than going through
`Livewire::test()` when you only care about SQL correctness:

```php
$definition = new TableDefinition(/* ... */);
$state = TableState::normalize(['search' => 'foo'], $definition);
$rows = (new TableQueryBuilder($definition))->paginate($state);

expect($rows->total())->toBe(2);
```

## Testing query count / N+1

```php
use Illuminate\Support\Facades\DB;

test('query count stays constant regardless of result set size', function () {
    // seed N rows with a relation...

    DB::enableQueryLog();
    (new TableQueryBuilder($definition))->paginate($smallPageState);
    $small = count(DB::getQueryLog());

    DB::flushQueryLog();
    (new TableQueryBuilder($definition))->paginate($largePageState);
    $large = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($small)->toBe($large);
});
```

## Testing custom columns/filters

Extend `Column`/`Filter` (see [extending.md](extending.md)) and unit test the fluent API and any
custom validation exactly like the built-in types are tested in
`tests/Unit/DynamicTable/ColumnTest.php` — construct the column, chain the fluent methods, assert
on the getters (`getLabel()`, `isSortable()`, etc.) and on any thrown exceptions for invalid
configuration.

## What's verified today

| Area | Test file |
|---|---|
| Column/filter/state definitions | `tests/Unit/DynamicTable/{ColumnTest,TableDefinitionTest,TableStateTest}.php` |
| Query compilation, operators, relations, N+1 | `tests/Feature/DynamicTable/QueryEngineTest.php` |
| Livewire interactions | `tests/Feature/DynamicTable/LivewireTableTest.php` |
| Preferences (load/save/reset/ownership/concurrency) | `tests/Unit/DynamicTable/TablePreferencesTest.php`, `tests/Feature/DynamicTable/PreferencesTest.php` |
| Saved views (CRUD/default/normalization/ownership) | `tests/Feature/DynamicTable/SavedViewsTest.php` |
| Adversarial/security | `tests/Feature/DynamicTable/SecurityHardeningTest.php` |
| Performance regression (count queries, pagination modes) | `tests/Feature/DynamicTable/PerformanceRegressionTest.php` |
