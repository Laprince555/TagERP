# Fields

All field types extend `App\Support\DynamicRecordView\Core\Fields\Field` and
live in that namespace:

`TextViewField`, `NumberViewField`, `MoneyViewField`, `BooleanViewField`,
`DateViewField`, `DateTimeViewField`, `EnumViewField`, `RelationViewField`,
`LinkViewField`, `ComputedViewField`.

## Shared API (`Field`)

`label()`, `visible()`/`hidden()` (bool or `callable(mixed $record): bool`),
`placeholder()` (default `—`), `formatUsing(callable(mixed $value, mixed $record): mixed)`,
`copyable()`, `badge()`, `icon()`, `color()`, `columnSpan()`, `tooltip()`.

`display($record)` resolves the value via `resolveValue()` (default:
`data_get($record, $key)`, dotted paths work), then applies `formatUsing()`
if set, otherwise the field type's own default formatting
(`formattedValue()`). Output is plain scalar text — Blade escapes it as
usual (`{{ }}`), there's no raw-HTML escape hatch.

## Type-specific options

- `MoneyViewField` — `->currency()`, `->decimals()` (default 2).
- `NumberViewField` — `->decimals()` (default 0).
- `DateViewField` / `DateTimeViewField` — `->format()`.
- `BooleanViewField` — `->labels($true, $false)`.
- `EnumViewField` — `->labels(['raw' => 'Display'])`.
- `RelationViewField` — no extra options; pass a dotted key like
  `'subModule.name'` (works off an eager-loaded relation via `data_get`).
- `LinkViewField` — `->linkUsing(fn ($record) => string|null)`, `->openInNewTab()`.
- `ComputedViewField` — `->using(fn ($record) => mixed)`; has no backing
  attribute, receives the whole record.

`FieldsContent::fields()` throws
`Exceptions\DuplicateFieldKeyException` on a repeated key.
