# Filters

All filters extend `App\Support\DynamicTable\Core\Filter`, take a unique `key`
(`Filter::make($key)`), and support dotted relation keys (`TextFilter::make('country.name')`).
Duplicate filter keys throw `DuplicateFilterKeyException`.

## Authorization

Every filter is optionally gated by `->visible(callable|bool $visible)`, exactly like
`Column::visible()`:

```php
// Filter-only field, no matching column — needs its own explicit condition:
TextFilter::make('internal_notes')->visible(fn (): bool => auth()->user()->can('view-internal-notes'));

// Filter attached to an authorized column — no explicit visible() needed; it
// automatically inherits the 'credit_limit' column's authorization:
NumberFilter::make('credit_limit');
```

See [security.md](security.md#filter-authorization) for the full resolution order. An unauthorized
filter is removed from Livewire state, the rendered filter panel, saved views, and query
compilation — not just hidden in the UI.

## Wire format

Filter state on the Livewire component is an array keyed by filter key:

```php
public array $filters = [
    'name' => ['operator' => 'contains', 'value' => 'Ada'],
    'active' => ['operator' => 'equals', 'value' => true],
];
```

`TableState::normalize()` is the only path this ever reaches the query engine through — every
operator string is checked against the filter's actual operator enum
(`TextOperator`/`NumberOperator`/`DateOperator`), never trusted as-is.

## Deferred apply pattern

Filter controls bind with a plain `wire:model` (no `.live`). `Table::applyFilters()` copies the
draft `$filters` into `$appliedFilters` and resets the page — so changing several filters and
clicking "Apply" costs exactly **one** Livewire request. `Table::clearFilters()` / `Table::clearFilter($key)`
reset one or all filters and re-apply immediately.

## Active filter chips & applied-vs-draft indication (✅ Implemented)

Every currently **applied** (not draft) filter renders as a removable chip above the filter panel,
built once per render by `Table::activeFilterChips()` — never in the Blade view itself. Each chip
shows the filter's label and a human-readable value summary (`Table::summarizeFilterValue()`) and
has its own `×` button wired to `clearFilter($key)`. A "Clear all" link next to the chips calls
`clearFilters()`.

If the draft `$filters` differs from the already-applied `$appliedFilters` (i.e. the user changed
a control but hasn't clicked Apply yet), an "Unapplied changes" indicator appears next to the
Apply button.

## `TextFilter` ✅

```php
TextFilter::make('name');
TextFilter::make('country.name'); // relation filter, via whereHas
```

Operators (`App\Support\DynamicTable\Core\TextOperator`): `Contains`, `Equals`, `StartsWith`,
`EndsWith`, `DoesNotContain`, `DoesNotEqual`, `IsEmpty`, `IsNotEmpty`.

`->operators(array $operators)` restricts which operators are offered; default is all of them.

## `NumberFilter` ✅

```php
NumberFilter::make('credit_limit');
```

Operators (`NumberOperator`): `Equals`, `DoesNotEqual`, `GreaterThan`, `GreaterThanOrEqual`,
`LessThan`, `LessThanOrEqual`, `Between`, `NotBetween`, `IsEmpty`, `IsNotEmpty`.

`Between`/`NotBetween` expect `value` to be a 2-element numeric array: `[10, 50]`. `0` is a valid
value and is never confused with `null`/`''` — `TableState::normalizeNumberValue()` uses
`is_numeric()`, not a truthiness check.

## `DateFilter` ✅

```php
DateFilter::make('created_at');
DateFilter::make('created_at')->withTime();                    // datetime precision instead of day precision
DateFilter::make('created_at')->timezone('Africa/Cairo');      // interpret submitted values in this timezone
```

Operators (`DateOperator`): `On`, `Before`, `BeforeOrOn`, `After`, `AfterOrOn`, `Between`,
`NotBetween`, `Today`, `Yesterday`, `ThisWeek`, `ThisMonth`, `IsEmpty`, `IsNotEmpty`.

### Strict parsing

Submitted values are parsed with `Carbon::createFromFormat()` against an **exact** format —
`Y-m-d` normally, `Y-m-d\TH:i` when `->withTime()` is set (matching `<input type="date">` and
`<input type="datetime-local">` respectively) — plus a round-trip check that re-formats the parsed
value and rejects it if it doesn't reproduce the original string. This catches both garbage input
(`'not-a-date'`, `'next monday'`, wrong-order formats like `'15-01-2026'`) and silently
auto-corrected invalid calendar dates (`'2026-02-30'`, which `Carbon::parse()` would loosely accept
by rolling over to March 2). Loose `Carbon::parse()` is never used for filter input.

### Timezone

`->timezone(string $timezone)` declares which timezone submitted values are interpreted in;
defaults to `config('app.timezone')` if not set. Every date/datetime bound is converted from that
timezone to the app/database timezone **before** being bound into the query — `TableQueryBuilder`
never re-derives day boundaries itself, because only `TableState` (Core) knows the filter's
timezone. This means "today" always means today in the filter's configured timezone, not the
database server's, and day-boundary math correctly accounts for DST transitions (a day can be 23
or 25 hours, not always exactly 24).

### Date vs. DateTime behavior

- **Day precision (default):** `On`/`Between`/etc. treat the submitted date as covering the
  filter timezone's full calendar day — inclusive of both the start and end of that day.
- **`->withTime()`:** the submitted value must include a time-of-day component; `On` matches the
  exact instant rather than a day range, and `Before`/`After`/etc. compare against that exact
  instant.

`Today`/`Yesterday`/`ThisWeek`/`ThisMonth` require no `value` from the client — computed from
`Carbon::now($filter->getTimezone())`, also converted to the app timezone before querying.

Verified in `tests/Feature/DynamicTable/DateFilterTimezoneTest.php`: day boundaries in a non-UTC
timezone, a real DST-crossing day, a user timezone differing from the database timezone, inclusive
`between` boundaries, exclusive-vs-inclusive `before`/`before_or_on`, invalid/malformed input
rejection, and `withTime()` datetime precision.

## `BooleanFilter` — tri-state ✅

```php
BooleanFilter::make('is_active');
```

No operator UI needed — value is `true`, `false`, or the filter key is simply absent from state
("All", i.e. no filter applied). `TableState::normalizeFilterEntry()` only accepts a real PHP
`bool`; anything else (a stray string, `null`) is dropped, so `false` is never confused with "no
filter."

## `EnumFilter` ✅

```php
EnumFilter::make('status')->enum(CustomerStatus::class)->multiple();
```

`->enum(class-string<BackedEnum>)` — throws `InvalidEnumConfigurationException` if not a backed
enum. `->multiple(bool = true)` accepts an array of values (clamped to
`TableState::MAX_MULTI_SELECT = 50`). Every submitted value — single or multiple — is validated
against `$enumClass::cases()`; a stale/removed/tampered enum value is silently dropped, never
reaches the query.

## `BelongsToFilter` ✅

```php
BelongsToFilter::make('country')
    ->displayUsing(fn ($option) => $option->name)
    ->searchUsing(['name', 'code'])
    ->multiple()
    ->async();
```

| Method | Effect |
|---|---|
| `relation(string $relationName)` | Overrides the filter key (relation name) if it differs from `make()`'s key |
| `multiple(bool = true)` | Accept multiple related IDs |
| `async(bool = true)` | Marks the filter as needing an async option list (search-as-you-type) rather than a preloaded `<select>` — see [relationships.md](relationships.md#large-relation-option-lists) |
| `displayUsing(callable $option): string` | How to render each option's label |
| `searchUsing(array $fields)` | Which related fields the async search matches against |

Submitted values are coerced to integers via `is_numeric()`/`(int)` casts — a non-numeric ID is
dropped, never passed to the query as a string.

### Async picker (✅ Implemented)

The filter panel renders a real search-as-you-type picker for `BelongsToFilter`, backed by
`Table::searchBelongsTo(string $filterKey, string $term)`:

- **No `Model::all()`** — every option list is a bounded, filtered query.
- **Minimum search length**: `Table::BELONGS_TO_MIN_SEARCH_LENGTH = 2` — shorter input clears the
  option list without querying at all.
- **400ms+ debounce**: the search input uses `wire:input.debounce.500ms`.
- **Bounded results**: `Table::BELONGS_TO_MAX_RESULTS = 20`.
- **Narrow select**: only the related model's primary key plus the fields declared via
  `->searchUsing([...])` (or the key itself if none declared) are selected — never the full row.
- **Respects the related model's own scopes**: options come from `$relation->getRelated()->newQuery()`,
  so soft-delete scopes, tenant global scopes, etc. defined on the related model still apply.
- **Labels are escaped**: rendered through Blade's `{{ }}`, never raw HTML.
- **Single and multiple selection**: `selectBelongsToOption()`/`removeBelongsToOption()`; multiple
  selections render as removable chips.
- **Previously selected labels resolve safely**: `Table::resolveBelongsToLabels()` (called once per
  render, only for filters with a current value — never in the Blade view itself) looks up the
  label for an already-selected id (e.g. restored from a saved view) without requiring the user to
  re-search.
- **No client-controlled relation/field names**: the relation always comes from the server-defined
  `BelongsToFilter::make($relationName)` — never from client input.
- **No query on mount**: options are empty until `searchBelongsTo()` is actually called.

```php
BelongsToFilter::make('country')
    ->displayUsing(fn ($country) => $country->name)
    ->searchUsing(['name', 'code'])
    ->multiple();
```

Verified in `tests/Feature/DynamicTable/BelongsToPickerTest.php`: minimum length, query-count
(zero below minimum, one at/above), field selection, result cap, label escaping, unauthorized-key
no-op, single/multiple select and remove, and previously-selected label resolution.

## Complete filter-panel example

```php
protected function filters(): array
{
    return [
        TextFilter::make('name'),
        TextFilter::make('country.name'),
        NumberFilter::make('credit_limit'),
        DateFilter::make('created_at'),
        BooleanFilter::make('is_active'),
        EnumFilter::make('status')->enum(CustomerStatus::class)->multiple(),
        BelongsToFilter::make('country')
            ->displayUsing(fn ($c) => $c->name)
            ->searchUsing(['name', 'code'])
            ->multiple()
            ->async(),
    ];
}
```
