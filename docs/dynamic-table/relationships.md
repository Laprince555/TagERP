# Relationships

## Supported relation types

| Relation type | Column display | Search | Filter | Sort |
|---|---|---|---|---|
| `BelongsTo` | ✅ `RelationColumn` | ✅ `whereHas` | ✅ dotted `TextFilter`/`BelongsToFilter` | ✅ correlated subquery |
| `HasOne` | ✅ `RelationColumn` | ✅ `whereHas` | ✅ dotted filter | ❌ Unsupported |
| `HasMany` | ✅ `RelationColumn` | ✅ `whereHas` (any-match) | ✅ dotted filter (any-match) | ✅ only with `->aggregate()` |
| `BelongsToMany` | ✅ `RelationColumn` | ✅ `whereHas` (any-match) | ✅ dotted filter (any-match) | ✅ only with `->aggregate()` |
| Nested (`a.b.c`) | ❌ Unsupported for sort; display/search/filter only resolve the **first** relation segment | — | — | ❌ |

## Column syntax

`RelationColumn::make('relation.field')` — the key is `{relationName}.{field}`:

```php
RelationColumn::make('author.name')->sortable()->searchable()->label('Author');

// Multiple related fields, all matched inside the SAME relation's whereHas() scope:
RelationColumn::make('author.name')->searchable(['bio', 'email']);
```

The extra fields passed to `->searchable([...])` on a `RelationColumn` are always interpreted as
additional fields on that **same related model** (`author` in the example — `bio`/`email` are
`author` table columns, never parent-table columns). They're matched with `orWhere` nested inside
the same `whereHas('author', ...)` closure the primary field uses, so the relation path is never
duplicated into a second query and a match on any of the fields still only requires one matching
related row (not one per field).

`getRelationPath()` returns everything before the last dot, `getRelationField()` everything after.
Only the first relation segment is resolved for eager-loading and sort purposes in this version —
`RelationColumn::make('author.company.name')` will eager-load `author` correctly but sort support
does not extend past one level.

## Filter syntax

```php
TextFilter::make('author.name');          // "any matching related record" via whereHas
BelongsToFilter::make('author')->multiple(); // filter by related IDs directly
```

## Preventing N+1

`TableQueryBuilder::applyEagerLoads()` eager-loads **only** the relation paths behind currently
visible (and authorized) `RelationColumn`s — never every relation the model defines. A hidden or
unauthorized `RelationColumn` never triggers `with()`.

```php
// ponytail: with($paths) loads full related rows, not select-narrowed per relation column.
// Still eliminates N+1 (the primary risk); narrow the related select() if payload size matters.
```

This means a table with 10 columns but only 2 visible `RelationColumn`s issues exactly one extra
query per unique relation path, regardless of row count — verified by
`tests/Feature/DynamicTable/QueryEngineTest.php`'s `'query count stays constant...'` test and
`PerformanceRegressionTest.php`.

## Relation search never duplicates parent rows

`whereHas()` is exists-based (a correlated subquery), not a join — so filtering/searching through
a `HasMany`/`BelongsToMany` relation can never fan out and duplicate the parent row, even when
multiple related records match. See the `'relation search does not duplicate parent rows'` test.

## Relation sorting limitations

- **`BelongsTo`**: sorted via a correlated subquery selecting the related field, ordered
  directly — no `withAggregate` needed.
- **`HasMany`/`BelongsToMany`**: sorting is inherently ambiguous (which related row wins?), so it
  is **rejected** (`HasManySortWithoutAggregateException`) unless `->aggregate('count'|'sum'|...)`
  is declared, which uses Eloquent's `withAggregate()`:

```php
RelationColumn::make('orders.total')->aggregate('sum')->sortable();
```

- **`HasOne`**: not supported for sorting in this version.
- **Multi-level paths** (`a.b.c`): not supported for sorting.

Attempting to sort an unsupported relation type either throws (`HasMany`/`BelongsToMany` without
an aggregate) or is silently ignored by `TableState::normalizeSorts()` if the column simply isn't
marked `sortable()`.

## Large relation option lists (`BelongsToFilter`)

`->async()` marks a `BelongsToFilter` as needing search-driven option loading instead of a
preloaded full list — critical for large related tables. The contract requirements (not all fully
wired into the shipped UI yet — see [filters.md](filters.md)):

- Never call `Model::all()` for the option list.
- Require a minimum search length before querying.
- Limit result count.
- Select only key + display/search fields.
- Respect authorization/tenancy scoping.
- Debounce requests.
