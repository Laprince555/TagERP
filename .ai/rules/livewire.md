---
paths:
  - 'resources/views/livewire/**, app/Livewire/**'
---

# Livewire

## UI: Flux UI + Livewire, always split into small components
All UI must be built with Flux UI components (`<flux:*>`) inside Livewire components — no raw hand-rolled markup/CSS when a Flux component exists for it. Use the `fluxui-development` and `livewire-development` skills when building any view.

Never dump large blade/markup into one big component. Whenever a piece of UI can be its own component (a card, a row, a modal, a table, a form section, a widget), extract it into its own Livewire component (or a Blade component for purely presentational, stateless pieces) instead of keeping everything crammed into one file. Prefer many small, focused, reusable components over one large monolithic one.
