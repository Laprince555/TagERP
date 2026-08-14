# Authorization

Status: **Implemented.** Every authorization hook in the engine, in the order
a request actually hits them.

1. **Record-level** — `DynamicRecordView::query()`. The only path a record is
   ever fetched through (`RecordResolver::resolve()`/`resolveFresh()`); rows
   excluded by this query 404 exactly like rows that don't exist.
2. **Tab-level** — `RecordTab::visible(bool|callable $condition)`, receiving
   the resolved record. `RecordSection::authorizedTabs()` filters to only
   visible tabs before anything renders; `defaultTabKey()`/
   `normalizeActiveTabKey()` only ever land on an authorized tab.
3. **Content-block-level** — `Content::visible(bool|callable $condition)`,
   same shape, checked before a `FieldsContent`/`TableContent` renders.
4. **Field-level** — `Field::visible()`/`Field::hidden()`, same shape, per
   individual field inside a `FieldsContent`.
5. **Sub Application-level** — `SubApplication::authorization(bool|callable $condition)`,
   which becomes the generated tab's `visible()` condition in
   `otherDataSection()`.
6. **Embedded-table-level** — `EmbeddedTableContext::resolveEmbeddedContent()`
   re-checks `$content->isVisible($parent)` on every embedded-table
   interaction (not just initial mount), 404ing if it's since become
   unauthorized.
7. **Relationship-mutation-level** — `RelationshipActions::linkAuthorization()`
   / `unlinkAuthorization(callable(?User $user, Model $parent, Model $candidate): bool)`,
   re-checked inside `RelationshipMutator`'s locked transaction immediately
   before the mutation, using the currently authenticated user
   (`auth()->user()`), not a value captured earlier in the request.

All conditions accept either a plain `bool` or a
`callable(mixed $record): bool` (field/content/tab/level) or
`callable(?User $user, Model $parent, Model $candidate): bool`
(link/unlink level) — never a Gate/Policy class reference is required, though
a condition closure is free to call `Gate::allows()`/`$user->can()` internally
if the app wants policy-backed authorization.

There is no separate "can view this record view at all" gate distinct from
`query()` — if an unauthenticated or unauthorized user's `query()` excludes
every row, every id 404s for them, which is the intended behavior (see
[security.md](security.md)).
