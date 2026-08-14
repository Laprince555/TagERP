# Accessibility

Status: **Partially implemented** — this documents the actual current state
honestly, not an aspirational target.

## Tabs (Implemented)

`resources/views/livewire/dynamic-record-view/record-view.blade.php` and
`other-data.blade.php` hand-roll tabs (not `<flux:tabs>`, since Flux Free's
tab component didn't fit the dynamic tab-count use case here). Both now
carry:

- `role="tablist"` on the tab strip container.
- `role="tab"`, a stable `id="{primary|other-data}-tab-{key}"`,
  `aria-selected`, and `aria-controls` pointing at the matching panel, on
  each tab `<button>`.
- `role="tabpanel"`, a matching `id="{primary|other-data}-tabpanel-{key}"`,
  and `aria-labelledby` on the rendered panel `<div>`.

Not implemented: keyboard arrow-key navigation between tabs (native only via
Tab/Shift+Tab through the buttons; no roving tabindex or Left/Right arrow
handling). Add this if a real accessibility audit flags it as required —
it's a reasonable next increment, not done in this pass.

## `copyable()` / `tooltip()` fields (Implemented, fixed this pass)

Before this pass, `Field::copyable()`/`tooltip()` were fluent no-ops — set on
the field definition but never read by
`resources/views/components/dynamic-record-view/fields-content.blade.php`.
Fixed:

- `tooltip()` renders as a native `title` attribute on the field's wrapper
  `<div>` — the simplest accessible tooltip mechanism (no extra JS, works
  with screen readers via the accessible-name computation, no custom
  positioning to maintain).
- `copyable()` renders a small icon button next to the value that calls
  `navigator.clipboard.writeText()` via a minimal Alpine `x-on:click`, with
  an `aria-label` ("Copy {field label}"). No confirmation toast is wired up
  — add one if a real "copied!" affordance is wanted; out of scope here.

## Dark mode (Implemented, spot-checked)

All of this engine's own Blade files (`fields-content.blade.php`,
`content.blade.php`, `record-view.blade.php`, `other-data.blade.php`,
`relation-picker-modal.blade.php`) use the same `dark:` token convention as
the rest of the app (e.g. `resources/views/layouts/app.blade.php`'s
`dark:border-zinc-700`/`dark:text-zinc-100` pairing) — borders/text/background
each carry a paired `dark:` variant, no unstyled dark-mode gaps found.

## RTL (Implemented, one fix this pass)

`relation-picker-modal.blade.php` used `text-left` on the candidate list
buttons — changed to the logical `text-start` so it flips correctly under
RTL locales. No other `ml-`/`mr-`/`pl-`/`pr-`/`left-`/`right-`/`text-left`/
`text-right` usage was found in this engine's own Blade files.

## Not reached / genuinely missing

- No automated accessibility test (e.g. axe-core via Pest browser testing)
  was added in this pass — the fixes above were verified by code
  inspection and the existing feature-test suite (which asserts HTML
  content, not ARIA semantics). Add an axe-core smoke test if accessibility
  regressions become a concern.
- No live-region (`aria-live`) announcement when a tab switch or Link/Unlink
  mutation completes — a screen reader user gets no explicit audible
  confirmation beyond the DOM update itself.
