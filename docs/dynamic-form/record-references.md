# Dynamic Form: Record References (status: no engine yet)

Confirmed again while implementing this feature: there is no shared Dynamic Form engine in this
codebase (no `app/Support/DynamicForm`, no `RecordReferenceField` consumer anywhere). Per
instructions, no fake/placeholder Dynamic Form framework was built. What follows is the contract a
real engine should target, plus the integration checklist for whoever builds it.

## Why the record-reference layer is already form-ready

Nothing in `app/Support/RecordReference` depends on Dynamic Table. `RecordReferenceProvider`,
`RecordReferenceRegistry`, `RecordReferenceResolver`, and the three Blade variants are
framework-agnostic — a Dynamic Form "pick a record" field can reuse every one of them for its
value display exactly the way the Dynamic Table column does.

## Documented `RecordReferenceField` contract

A future Dynamic Form field type should look like this (mirrors `RecordReferenceColumn`'s shape):

```php
final class RecordReferenceField extends Field // whatever the eventual base Field class is
{
    public function applicationCode(string $code): static;   // trusted, developer-declared
    public function variant(RecordReferenceVariant $variant): static; // display variant for the current value
    public function getApplicationCode(): string;
    public function getVariant(): RecordReferenceVariant;
}
```

Responsibilities split the same way as the column:

- **Value storage**: the field stores/binds a scalar record key (e.g. a `country_id` foreign key)
  — never a model, provider, or route.
- **Display**: given the bound record (already loaded by the form, exactly like a Dynamic Table
  row), build `RecordReferenceIdentity`/facts via `RecordReferenceResolver` and render
  `<x-record-reference.card|tag|icon>` — identical call to the Dynamic Table integration.
- **Picking a new value** (search/select UI) is a genuinely new concern the field owns — it is not
  part of `RecordReferenceProvider`. The provider only describes *how to present* a record once
  chosen, not how to search for one. A picker would need its own trusted, provider-declared query
  (e.g. `RecordReferenceProvider::searchable(): bool` + a scoped search query) — deliberately not
  designed here since no consumer exists yet to validate the shape against.
- **Authorization**: reuse `RecordReferenceProvider::authorize()`/`scopeQuery()` for both display
  and (when built) the picker's query, so the two surfaces can never diverge.

## Integration checklist for when a Dynamic Form engine lands

1. Add `RecordReferenceField` to the form engine's field registry, following its established
   `Field` base class conventions (validation, casting, etc. — whatever they turn out to be).
2. Reuse `RecordReferenceRegistry::resolve()` to look up the provider from `applicationCode()`.
3. Reuse `RecordReferenceResolver` for display; do not duplicate title/fact construction.
4. If a picker UI is needed, extend `RecordReferenceProvider` with an opt-in search method rather
   than bypassing it — keep the "one Application-owned definition" invariant from
   `docs/record-references/README.md` intact.
5. Add Pest tests mirroring `tests/Feature/RecordReference/DynamicTableRecordReferenceColumnTest.php`
   (visible/hidden facts, authorization, forged input) once the field exists.

No code changes were made in this area beyond this document — there is nothing to wire up yet.
