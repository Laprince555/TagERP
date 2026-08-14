# Relation Picker

`App\Support\DynamicRecordView\Core\RelationPicker` configures the searchable
candidate picker used by `RelationshipActions::linkExisting()`. Rendered by
`App\Livewire\DynamicRecordView\RelationPickerModal` (a Flux Free
`<flux:modal>`), one instance per embedded `TableContent` with
`relationshipActions()->isLinkable()` — see
`resources/views/components/dynamic-record-view/content.blade.php`.

## Public API

```php
RelationPicker::make(): static
->query(Closure $callback): static              // fn(Builder $query): Builder — additional server-defined constraint
->searchable(array $fields): static             // allowlisted searchable columns
->displayUsing(Closure|string $field): static    // field name (dot notation) or fn($record): string
->pageSize(int $size): static                    // default 5
->maximumLoadedResults(int $max): static         // default 50
->getQuery(): ?Closure
->getSearchable(): array
->display(Model $record): string
->getPageSize(): int
->getMaximumLoadedResults(): int
```

`RelationPicker::MAX_SEARCH_LENGTH = 100` bounds accepted search input.

## `RelationPickerModal` behavior

- **No query before open.** `mount()` only sets `#[Locked]` scalar props
  (recordViewKey, recordId, section, tab, contentKey, tableClass — the same
  bounded identifiers `Table`'s embed props carry). Candidates are queried
  only from `openPicker()`, triggered by the toolbar Link button dispatching
  a scoped `open-relation-picker.<instanceIdentifier>` event (see
  `resources/views/livewire/dynamic-table/table.blade.php`).
- **Opening loads at most `pageSize()` candidates** (default 5).
- **Search** is bound `wire:model.live.debounce.400ms` (see
  `relation-picker-modal.blade.php`) against the allowlisted `searchable()`
  fields only — never an arbitrary client-supplied column.
- **LIKE wildcards are escaped**: `addcslashes($term, '\\%_')` before
  building the `where(... like '%term%')` clause, so a `%` or `_` in user
  input is matched literally, not as a wildcard.
- **Arabic/Unicode search** works — the search term is only ever passed
  through `mb_substr`/plain SQL `LIKE`, no ASCII-only assumptions.
- **Stable ordering**: `orderBy($searchFields[0] ?? $keyName)->orderBy($keyName)`
  — the primary key is always the tie-breaker.
- **Load more**: `loadMore()` fetches the next `pageSize()` candidates,
  excluding already-loaded ids (`whereNotIn($keyName, $loadedIds)`) and
  fetching one extra row to derive `hasMore` — **no `COUNT()` query anywhere**
  in the picker's flow.
- **Search resets pagination**: `updatedSearch()` clears `results`/`hasMore`
  before re-querying.
- **Already-linked candidates are excluded**: the candidate query always
  carries `whereNotIn($keyName, <ids currently related to this parent>)`
  (via the relation's own query), regardless of search term.
- **Bounded accumulation**: once `count($results) >= maximumLoadedResults()`
  (default 50), `loadResults()` stops loading and the view shows "Refine
  your search to see more results." instead of a Load More button.
- **Minimal `SELECT`**: the candidate query only ever selects the primary key
  plus the declared `searchable()` fields — never `SELECT *`.
- **Single selection**: `selectCandidate($id)` only sets `selectedId` — no
  mutation happens until the explicit `confirmLink()` (the "Link" button)
  click. Multi-select is not implemented — Planned if a future relation needs
  it.
- **State clears on open/close**: `openPicker()` resets `search`, `results`,
  `hasMore`, `error`, `selectedId`; `confirmLink()` resets the same state
  after a successful link and dispatches `close-relation-picker.<modalName>`
  to close the Flux modal.
- **Loading/empty/error states**: `wire:loading` around the results list,
  an empty-state message when `results` is `[]`, and `$error` surfaced as
  text when the picker isn't configured or the link attempt fails.

## Wiring the mutation

`confirmLink()` calls `RelationshipMutator::link()` (see
[relationship-actions.md](relationship-actions.md)) with the selected id, then
dispatches `relationship-linked.<instanceIdentifier>` — the owning embedded
`Table` listens for exactly that scoped event name (see
`App\Livewire\DynamicTable\Table::getListeners()`) and re-renders itself in
the same request cycle, satisfying "refresh the embedded table after
mutation."

See `tests/Feature/DynamicRecordView/RelationshipActionsTest.php` for
coverage of: no query before open, ≤5 on open, already-linked exclusion,
search reset/re-query, wildcard/SQL-injection/Arabic search safety,
maximumLoadedResults cutoff, and bounded query count across an
open+search+load-more sequence.
