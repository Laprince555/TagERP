# Relationship Actions (Link / Unlink)

`App\Support\DynamicRecordView\Core\RelationshipActions` configures Link/
Unlink mutation support for one embedded relation. Disabled by default —
both actions must be explicitly enabled. Attach it to a `TableContent` or
`SubApplication` that already declares `->relation(...)`:

```php
TableContent::make('applications')
    ->table(ApplicationsTable::class)
    ->relation('applications')
    ->relationshipActions(
        RelationshipActions::make()
            ->linkExisting(
                RelationPicker::make()
                    ->displayUsing('name')
                    ->searchable(['name', 'code'])
                    ->pageSize(5)
                    ->maximumLoadedResults(50),
            )
            ->linkAuthorization(fn ($user, $parent, $candidate) => $user !== null)
            ->unlink()
            ->unlinkAuthorization(fn ($user, $parent, $related) => $user->can('manage', $parent)),
    );
```

## Public API

```php
RelationshipActions::make(): static
->linkExisting(RelationPicker $picker): static       // enables Link
->linkAuthorization(callable $callback): static       // fn($user, $parent, $candidate): bool
->unlink(): static                                     // enables Unlink
->unlinkAuthorization(callable $callback): static      // fn($user, $parent, $related): bool
->unlinkConfirmationText(string $text): static
->allowReassignment(): static                          // see "HasMany reassignment" below
->mutateUsing(callable $callback): static              // fn($parent, $relation, $subject, string $action)
->reassignPolicy(mixed $policy): never                 // PLANNED — always throws, see below
->isLinkable(): bool
->isUnlinkable(): bool
->getPicker(): ?RelationPicker
->getLinkAuthorization(): ?Closure
->getUnlinkAuthorization(): ?Closure
->getUnlinkConfirmationText(): string
->reassignmentAllowed(): bool
->getMutateUsing(): ?Closure
->assertSupportedFor(string $parentModelClass, string $relationName): void  // config-time guard, see below
```

`TableContent::relationshipActions(RelationshipActions $actions)` /
`SubApplication::relationshipActions(RelationshipActions $actions)` attach it.
`DynamicRecordView::otherDataSection()` copies a `SubApplication`'s
`relationshipActions()` onto the `TableContent` it builds, and calls
`assertSupportedFor()` on it — see the config-time guard below.

## Execution: `RelationshipMutator`

`App\Support\DynamicRecordView\Resolution\RelationshipMutator` is the only
place a Link/Unlink actually happens. Both `link()` and `unlink()`:

1. Re-resolve and authorize the parent + content block through
   `EmbeddedTableContext::resolveEmbeddedContent()` — the same trusted
   fresh-resolution path `constrain()` (the read path) uses, so the two never
   diverge on what counts as an authorized parent/content block.
2. Resolve the relation from the server definition via
   `EmbeddedTableContext::resolveRelation()` — never a client-supplied name.
3. Re-fetch the candidate/related row by id through the related model's own
   query, `lockForUpdate()`'d — never trusts client-supplied row data.
4. Verifies the candidate/related model matches the relation's related class
   (enforced by `resolveRelation()`'s `$expectedRelatedClass` check).
5. Runs the link/unlink authorization callback with real model instances.
6. Executes inside `DB::transaction()` — see `link()`/`unlink()` in
   `RelationshipMutator.php`.
7. `BelongsToMany` uses `syncWithoutDetaching()` for Link (no duplicate pivot
   rows) and `detach()` for Unlink (pivot row only, neither model deleted).
   No `BelongsToMany` relation exists yet in this codebase — this path is
   implemented and unit-testable in isolation but untested against a real
   relation; wire one when a real use case appears.
8. Every read/write inside the transaction is re-locked/re-fetched there, not
   just before it — a concurrent request that changed the parent or
   candidate between the pre-transaction check and now is caught by the
   fresh `lockForUpdate()` lookups inside the closure.
9. `App\Livewire\DynamicTable\Table::unlinkRelated()` calls
   `RelationshipMutator::unlink()` directly in the same Livewire request that
   rendered the row, and `RelationPickerModal::confirmLink()` dispatches a
   `relationship-linked.<instanceIdentifier>` event the owning `Table`
   listens for (see `Table::getListeners()`) to re-render itself.
10. Every abort path (`safeAbortUnless()`) raises a flat 422 with a generic
    "Unable to complete this action." message — never "record #47 doesn't
    exist" vs. "not authorized", so a client can't distinguish the two.

## HasMany semantics and the non-nullable FK problem

For a `HasMany`/`MorphMany` relation, Link assigns the child's foreign key to
the parent, and Unlink sets it to `null`.

- **Unlink requires a nullable FK.** `RelationshipActions::assertSupportedFor()`
  is a **config-time** guard: called from `DynamicRecordView::otherDataSection()`
  whenever a `SubApplication` has both `->relation(...)` and
  `->relationshipActions()->unlink()` configured, and defensively again inside
  `RelationshipMutator::unlink()`. It inspects the relation's foreign key
  column via `Schema::getColumns()` and throws
  `UnsupportedUnlinkForNonNullableForeignKeyException` immediately if the
  column is `NOT NULL` — Unlink on such a relation fails loudly as soon as
  it's declared, never silently at the first click.
- **Link on a `NOT NULL` FK is necessarily a reassignment.** A `NOT NULL`
  foreign key means no row is ever "unassigned" — there is no null-FK state
  to adopt into. By default, Link only assigns a currently-`NULL` FK (never
  steals a child from a different, live parent). `allowReassignment()` is the
  explicit, minimal opt-in that instead moves a child from whichever parent
  it currently belongs to onto the new one. It changes ownership only — it
  never deletes either row, matching this project's non-negotiable "never
  delete on unlink" rule.
- **Idempotency**: linking a candidate that already belongs to the target
  parent is a safe no-op (both `HasMany`/`MorphMany` and `BelongsToMany`
  paths check this before writing).

### Judgment call: why `allowReassignment()` is real, not a stub

The spec for this pass asked for "an optional reassignment policy... leave
as a documented no-op/Unsupported stub — there's no real requirement for it
here." In practice, every reachable relation in this codebase
(`Application.submodule_id`, `SubModule.module_id`, and `nnjeim/world`'s
`cities.country_id`) has a `NOT NULL` foreign key. Under a strict
"unassigned-only" default, **no candidate could ever be linked** for any of
them — the mandated `SubModule -> Applications` Link demo would be
permanently non-functional, and "authorized link succeeds and creates the
correct relation state" (an explicitly required test) would be untestable
without it. `allowReassignment()` is the smallest possible real
implementation of the exact "reassign()-style explicit opt-in" the spec's own
wording names as the correct escape hatch — a single boolean, off by default
everywhere else, on only for `SubModule -> Applications` in
`SubModuleRecordView`. No policy object, no audit trail, no notification
hooks were added; `reassignPolicy()` exists only as a `never`-returning stub
that throws if called, so nothing can silently assume a richer policy exists.

## Working example: `SubModule -> Applications`

`Modules\General\System\SubModuleRecordView::subApplications()` wires
`relationshipActions()` onto the real `applications` `HasMany` relation:
Link-only, `allowReassignment()` on, `unlink()` never called (so the
non-nullable-FK guard never fires in normal operation). See
`tests/Feature/DynamicRecordView/RelationshipActionsTest.php` for the full
Link mutation coverage (success/idempotent/forged-id/unauthorized/
transaction-rollback).
