# Dynamic Form: Record References (status: engine exists, `RecordReferenceField` not built)

The Dynamic Form engine (`app/Support/DynamicForm`) now exists — see [README.md](README.md) and
[fields.md](fields.md) for its six field types. None of them is a `RecordReferenceField` yet, and
nothing in the codebase consumes `RecordReferenceProvider` from a form. What follows is still the
target contract, plus the integration checklist for whoever builds it — unchanged in substance,
only the framing above was stale.

## Why the record-reference layer is already form-ready

Nothing in `app/Support/RecordReference` depends on Dynamic Table. `RecordReferenceProvider`,
`RecordReferenceRegistry`, `RecordReferenceResolver`, and the three Blade variants are
framework-agnostic — a Dynamic Form "pick a record" field can reuse every one of them for its
value display exactly the way the Dynamic Table column does.

## Documented `RecordReferenceField` contract

A future Dynamic Form field type should look like this (mirrors `RecordReferenceColumn`'s shape,
extending the real `App\Support\DynamicForm\Core\Field` — see [fields.md](fields.md#shared-api-field)
for its `label()`/`required()`/`placeholder()`/`helpText()`/`rules()` base):

```php
final class RecordReferenceField extends Field
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
  (e.g. `RecordReferenceProvider::searchable(): bool` + a scoped search query). `RelationListField`
  (see [fields.md](fields.md#relationlistfield)) is now the working precedent for this exact
  shape — bounded search, `pageSize()`/`maximumLoadedResults()`, a `query()` scope closure — a
  `RecordReferenceField` picker should reuse that same UX pattern rather than inventing a new one.
- **Authorization**: reuse `RecordReferenceProvider::authorize()`/`scopeQuery()` for both display
  and (when built) the picker's query, so the two surfaces can never diverge.

## Integration checklist for when `RecordReferenceField` is built

1. Add `RecordReferenceField extends App\Support\DynamicForm\Core\Field` and register it the same
   way every other field type is consumed — no field registry beyond `DynamicForm::fields()`
   returning it; see [fields.md](fields.md#shared-api-field).
2. Reuse `RecordReferenceRegistry::resolve()` to look up the provider from `applicationCode()`.
3. Reuse `RecordReferenceResolver` for display; do not duplicate title/fact construction.
4. Model the picker on `RelationListField` + `App\Livewire\DynamicForm\Form`'s existing
   `openRelationPicker()`/`loadRelationResults()`/`selectRelation()` methods (see
   [hosting-and-events.md](hosting-and-events.md)), constrained through
   `RecordReferenceProvider::scopeQuery()` rather than an ad-hoc `query()` closure — keep the
   "one Application-owned definition" invariant from `docs/record-references/README.md` intact.
5. Add Pest tests mirroring `tests/Feature/DynamicForm/CompanyFormTest.php`'s relation-picker
   coverage (visible/hidden facts, authorization, forged input) once the field exists.

No code changes were made in this area beyond this document — there is nothing to wire up yet.
