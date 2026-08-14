# Extending the Engine

## Creating a custom column

Extend `App\Support\DynamicTable\Core\Column` (abstract) and override `formatValue()` for custom
display logic — see `NumberColumn`/`MoneyColumn`/`DateColumn` for the pattern:

```php
namespace App\Support\DynamicTable\Core\Columns;

use App\Support\DynamicTable\Core\Column;

class PercentageColumn extends Column
{
    protected int $decimals = 1;

    public function decimals(int $decimals): static
    {
        $this->decimals = $decimals;

        return $this;
    }

    public function formatValue(mixed $value, mixed $row): mixed
    {
        if ($value === null || $value === '') {
            return $this->placeholder ?? $value;
        }

        if ($this->formatUsing) {
            return ($this->formatUsing)($value, $row);
        }

        return number_format((float) $value * 100, $this->decimals).'%';
    }
}
```

No registration step is needed — a table simply uses `PercentageColumn::make('conversion_rate')`
like any built-in column. The Blade table partial renders any `Column` subclass generically via
`formatValue()`/`getLink()`; a filter-panel-style `instanceof` check is only needed if the new
column requires bespoke UI (rare — most columns are display-only).

To add validation that fails fast at definition time (like `ComputedColumn`'s guard), override
the fluent method and throw a `DynamicTableException` subclass:

```php
public function sortable(bool $sortable = true): static
{
    if ($sortable && ! $this->someRequiredConfigIsSet()) {
        throw SomeConfigException::forKey($this->getKey());
    }

    return parent::sortable($sortable);
}
```

## Creating a custom filter

Extend `App\Support\DynamicTable\Core\Filter` and add a case to
`TableQueryBuilder::applyFilter()`'s `match` — this is the one place the query engine needs to
know about your filter's SQL translation (Core layer stays framework-agnostic; the query
translation lives in the Query layer, per the architecture split):

```php
// Core/Filters/CustomRangeFilter.php
class CustomRangeFilter extends Filter {}

// Query/TableQueryBuilder.php, in applyFilter()'s match(true):
$filter instanceof CustomRangeFilter => $this->scopedWhere($query, $filter, fn ($q, $field) => /* ... */),
```

Also add a `normalizeFilterEntry()` case in `TableState` so untrusted client input for your filter
is validated before it reaches the query engine — this is the security boundary and **must not be
skipped**.

## Adding a search driver

🔮 Planned extension point, not yet built. The intended contract (per the original spec) is a
`SearchDriver` interface the query engine's `applySearch()` would delegate to, with the current
`LIKE`-based implementation as the default `DatabaseSearchDriver`. Adding a Scout-backed driver
later means implementing that interface — `Core` and `Query` layers were kept dependency-free of
any specific driver for exactly this reason.

## Adding another preference store

Implement `App\Support\DynamicTable\Core\TablePreferenceStore` and rebind it in a service
provider:

```php
$this->app->bind(TablePreferenceStore::class, RedisTablePreferenceStore::class);
```

Same pattern for `SavedTableViewStore`. Both contracts live in `Core` and take no dependency on
Eloquent, so a Redis-, cache-, or session-backed implementation is a drop-in replacement.

## Creating another UI renderer

`Core` and `Query` have zero dependency on Livewire or Flux — only `app/Livewire/DynamicTable/`
and the Blade views do. A non-Livewire renderer (e.g. a plain controller + Inertia/Vue page) would
consume `TableDefinition` + `TableState` + `TableQueryBuilder` directly and render its own
markup — no changes to `Core`/`Query` required.

## Public extension points vs. internal APIs

| Public (safe to extend/depend on) | Internal (may change without notice) |
|---|---|
| `Column`, `Filter` base classes and their public fluent methods | `TableQueryBuilder`'s protected `apply*` methods |
| `TablePreferenceStore`, `SavedTableViewStore` contracts | `TablePreferences::normalize()`'s exact merge algorithm |
| `Table` (Livewire base class) abstract methods (`columns()`, `filters()`, `query()`, `defaultSort()`) | `Table`'s protected helper methods (`rawState()`, `preferenceStore()`, etc.) |
| `TableState::normalize()` (the security boundary — safe to call directly for custom renderers) | `TableState`'s protected `normalize*` helpers |
