# Security

Status: **Implemented.** This documents the hardening actually in place, with
real code references.

## URL scheme allowlist (`LinkViewField`)

`App\Support\DynamicRecordView\Core\Fields\LinkViewField::getUrl()` only
renders a URL built by `linkUsing()` if `isSafeUrl()` accepts it:

- Relative/internal URLs (no scheme, e.g. `/customers/1`) are always allowed.
- Absolute URLs must use `http`, `https`, `mailto`, or `tel`
  (`LinkViewField::ALLOWED_SCHEMES`). Anything else — `javascript:`, `data:`,
  `vbscript:`, etc. — is silently dropped (`getUrl()` returns `null`, so the
  field renders no link at all rather than an unsafe one).
- Control characters (`\x00`-`\x1f`) anywhere in the URL reject it outright.

See `tests/Feature/DynamicRecordView/SecurityHardeningTest.php` for the
rejected-scheme cases.

## Fail-closed registry

`App\Support\DynamicRecordView\Core\RecordViewRegistry` only resolves a
`DynamicRecordView` for a key that was explicitly `register()`-ed.
An unknown key throws `UnknownRecordViewKeyException` before anything is
instantiated — there is no fallback, wildcard, or convention-based lookup
that could resolve a view the developer didn't register.

## Authorized resolution only

`App\Support\DynamicRecordView\Resolution\RecordResolver::resolve()` never
calls `Model::findOrFail()`. It always goes through the view's own
`query()`, so a record excluded by that query (soft delete, tenant scope,
explicit authorization `where()`) 404s exactly like a record that doesn't
exist — a tampered id can't distinguish "doesn't exist" from "exists but you
can't see it". `resolveFresh()` drops the per-request memoization cache so a
record deleted or de-authorized between an initial render and a later
Livewire action is caught on the very next action, not just at mount.

## Transactional, re-checked, re-locked mutation

`App\Support\DynamicRecordView\Resolution\RelationshipMutator::link()` /
`unlink()`:

1. Re-resolve the parent + content block fresh through
   `EmbeddedTableContext` — the same trusted path reads use — never trusting
   a client-supplied relation name, model class, or SQL field. Only bounded
   scalar identifiers (`recordViewKey`, parent id, section, tab, content key,
   candidate/related id) ever cross the wire.
2. Open a `DB::transaction()` and re-fetch both parent and
   candidate/related row with `lockForUpdate()` — a concurrent request may
   have changed either row since the pre-transaction resolution.
3. Re-check link/unlink authorization (`getLinkAuthorization()` /
   `getUnlinkAuthorization()`) inside the lock.
4. Every failure path (`safeAbortUnless()`) throws a generic
   `HttpException(422, 'Unable to complete this action.')` — never a message
   that distinguishes "doesn't exist" from "not authorized", so a client
   can't probe for valid ids by comparing error text.

## Non-nullable-FK Unlink is architecturally rejected

For a `HasMany`/`MorphMany` relation whose foreign key is `NOT NULL`
(both canonical examples: `Application.submodule_id`, `City.country_id`),
an ordinary Unlink would require setting that FK to `null`, which the schema
forbids. `RelationshipActions::assertSupportedFor()` and
`RelationshipMutator::performUnlink()` do not special-case this — Unlink
simply isn't offered; only `allowReassignment()`-gated Link (re-pointing the
FK to a new parent) is available. This is a real schema limitation, not a
missing feature — see [relationship-actions.md](relationship-actions.md) and
[troubleshooting.md](troubleshooting.md).

## State normalization

`RecordSection::normalizeActiveTabKey()` rejects any client-supplied active
tab candidate that is empty, longer than `RecordSection::MAX_TAB_KEY_LENGTH`
(100 chars), not a defined tab key, or not currently authorized for the
resolved record — falling back to the default authorized tab in every case.
`RecordView` and `OtherData` both self-heal `activeTab` through this method
at render time (not just on the explicit `setActiveTab` action), so a stale
or tampered `activeTab` property can never render unauthorized tab content.

## What this does not cover

- No CSRF-specific code here — Livewire's own CSRF protection covers all
  action calls (`link`, `unlink`, `setActiveTab`, etc.); this engine adds no
  additional token handling and needs none.
- No rate limiting on the Relation Picker search — out of scope for this
  engine; apply route/middleware-level throttling if a specific picker needs
  it.
