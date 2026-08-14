# Columns

All column types extend `App\Support\DynamicTable\Core\Column` and share its fluent API. Every
column has a unique `key` (`Column::make($key)`); duplicate keys within one table throw
`DuplicateColumnKeyException` at definition time.

## Shared fluent API (✅ Implemented)

| Method | Effect |
|---|---|
| `label(string $label)` | Display label. Defaults to `Str::headline($key)` if omitted. |
| `sortable(bool $sortable = true)` | Allows this column in `sorts` state. |
| `searchable(bool\|array $searchable = true)` | Include in global search. An array adds extra fields to search alongside the column's own field (used by `RelationColumn`/`BelongsToFilter`-style multi-field search). |
| `hiddenByDefault(bool $hidden = true)` | Column exists and is authorized, but starts hidden until the user shows it. |
| `toggleable(bool $toggleable = true)` | Whether the user can hide/show this column at all. `toggleable(false)` = fixed column; user state can never hide it. |
| `visible(callable\|bool $visible)` | **Authorization gate.** `false` (or a callback returning `false`) removes the column from everywhere — see [visibility-authorization.md](visibility-authorization.md). |
| `formatUsing(callable $callback)` | `fn($value, $row) => mixed` — custom display formatting. |
| `placeholder(string $placeholder)` | Text shown when the value is `null` or `''`. |
| `link(callable $callback)` | `fn($row): ?string` — wraps the cell in a link. **The returned URL is validated server-side**: only `http`/`https` (or relative) schemes are kept; anything else (e.g. `javascript:`) is silently dropped. |
| `align(string $align)` | Alignment hint for the renderer. |
| `width(string $width)` | Width hint for the renderer. |
| `exportable(bool $exportable = true)` | **Config flag only** — no exporter is implemented yet (🔮 Planned). |

## Column types

### `TextColumn::make($key)` ✅
Plain string display, no extra config beyond the shared API.

```php
TextColumn::make('name')->sortable()->searchable()->label('Name');
```

### `NumberColumn::make($key)` ✅
```php
NumberColumn::make('quantity')->decimals(0);
```
`->decimals(int)` controls `number_format()` precision. Formats via `number_format()` unless
`formatUsing()` is set.

### `MoneyColumn::make($key)` ✅ (extends `NumberColumn`)
```php
MoneyColumn::make('credit_limit')->currency('EGP')->decimals(2)->sortable();
```
`->currency(string)` prefixes the formatted number, e.g. `"EGP 1,250.00"`.

### `DateColumn::make($key)` ✅
```php
DateColumn::make('created_at')->format('Y-m-d')->sortable();
```
`->format(string)` — a Carbon format string, default `Y-m-d`. Non-`CarbonInterface` values are
passed through unformatted.

### `DateTimeColumn::make($key)` ✅ (extends `DateColumn`)
Same API, default format `Y-m-d H:i`.

### `BooleanColumn::make($key)` ✅
```php
BooleanColumn::make('is_active')->labels('Yes', 'No')->sortable();
```
`->labels(string $true, string $false)` sets the display text (default `"Yes"`/`"No"`).

### `EnumColumn::make($key)` ✅
```php
EnumColumn::make('status')->enum(CustomerStatus::class)->sortable();
```
`->enum(class-string<BackedEnum>)` — throws `InvalidEnumConfigurationException` if the class is
not a backed enum. Displays `$case->name` by default; override with `formatUsing()`.

### `RelationColumn::make('relation.field')` ✅
Dotted key = relation path + field, e.g. `RelationColumn::make('country.name')`.
Throws `UnsupportedRelationPathException` if the key has no dot.

```php
RelationColumn::make('country.name')->sortable()->searchable()->label('Country');
```

`->aggregate(string $function)` declares an aggregate (e.g. `'count'`) that makes a to-many
relation column sortable — see [relationships.md](relationships.md). Without it, sorting a
`HasMany`/`BelongsToMany` relation column throws `HasManySortWithoutAggregateException`.

### `ComputedColumn::make($key)` ✅
A column whose display value isn't a plain field — e.g. derived text.

```php
ComputedColumn::make('email_domain')
    ->field('email') // declares the REAL underlying field this is derived from
    ->formatUsing(fn ($value) => str($value)->after('@')->toString())
    ->label('Domain');
```

**Restriction (fails fast, deterministically, at `TableDefinition` construction time):**
`->sortable()`/`->searchable()` without a declared `->field()` throws
`SortableComputedColumnWithoutDataSourceException` / `FilterTargetUnavailableException`. This is
validated once, via `Column::validate()`, after the column's entire fluent chain has already run —
**call order does not matter**:

```php
// Without field(), both throw identically regardless of call order:
ComputedColumn::make('x')->sortable();                // throws at TableDefinition construction
ComputedColumn::make('x')->sortable()->searchable();   // throws — same exception type either way

// With field() declared anywhere in the chain, both are valid and equivalent:
ComputedColumn::make('x')->field('real_field')->sortable()->searchable();
ComputedColumn::make('x')->sortable()->searchable()->field('real_field');
```

## Invalid / unsupported configurations

| Configuration | Result |
|---|---|
| Two columns with the same key in one table | `DuplicateColumnKeyException` at `TableDefinition` construction |
| `RelationColumn::make('no_dot')` | `UnsupportedRelationPathException` |
| `RelationColumn::make('author.')` or `RelationColumn::make('.name')` (empty relation or field segment) | `UnsupportedRelationPathException` |
| `EnumColumn::make('x')->enum(SomeNonEnumClass::class)` | `InvalidEnumConfigurationException` |
| `ComputedColumn` sortable/searchable without `->field()` declared anywhere in the chain | `SortableComputedColumnWithoutDataSourceException` / `FilterTargetUnavailableException` |
| Sorting a `HasMany`/`BelongsToMany` `RelationColumn` without `->aggregate()` | `HasManySortWithoutAggregateException` (thrown when that sort is actually applied) |
| `defaultSort()` referencing a column key that doesn't exist in `columns()` | `UnknownFieldMappingException` at `TableDefinition` construction |
| A `Table` subclass with no `$model` property and no `query()` override | `InvalidModelException` |
| `protected ?string $model = SomeNonModelClass::class;` | `InvalidModelException` |

## Export visibility

`exportable()` only stores a boolean on the column. **No exporter reads it in this version** —
it exists so future export tooling has an authoritative per-column flag from day one, without
having to touch every table definition later.
