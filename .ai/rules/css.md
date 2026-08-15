---
paths:
  - 'resources/views/**, Modules/**/Resources/views/**, resources/css/app.css'
---

# Css

## Theme colors: semantic tokens only, never `dark:` or palette utilities
Theming is `data-theme` on `<body>` (see `resources/views/layouts/app.blade.php`), not Tailwind's `dark:` variant. A `dark:` class fires off the OS setting and will fight the active theme — never use one. Same for palette utilities (`bg-white`, `text-zinc-500`, `border-emerald-200`): they don't adapt.

Use the semantic utilities defined in `resources/css/app.css`:
`bg-card` `bg-canvas` `bg-surface-0/1/2` `bg-primary-soft` `border-app` `text-main` `text-muted` `text-danger` `text-success` and the `*-ledger-in/out/allocated(-subtle)` family.

Trap: any token derived from another (`--color-card: var(--color-card-bg)`, the `--color-surface-*` mixes) MUST be declared under the `:root, [data-theme], [class*='theme-']` selector list in section 2b — a `:root`-only declaration computes once against the default theme and inherits down frozen, so `bg-card` etc. silently stop following the theme. Names re-declared inside `@theme` must be self-referential (`--color-card: var(--color-card)`) so the unlayered value wins.

Literal hex is correct in exactly one place: the theme-switcher swatches, which preview a specific theme.
