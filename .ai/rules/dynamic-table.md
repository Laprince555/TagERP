---
paths:
  - 'app/Support/DynamicTable/**,app/Livewire/DynamicTable/**,resources/views/components/dynamic-table/**'
---

# Dynamic Table

## A filter with no value must normalize away, and never interpolate boolean attributes
Tables seed a default operator for every text/number/date filter, so an untouched filter arrives at normalization as e.g. contains + null. TableState must drop those: casting null to string produced `LIKE '%%'`, which is NULL rather than true for any row whose column is NULL, so an untouched Number filter silently hid every journal without a number. Any new filter type has to drop a valueless entry the same way.

Filter chips are built from the NORMALIZED applied state, not the raw draft, so a chip never claims a filter the query ignored.

Blade: `multiple="{{ $flag }}"` renders `multiple=""` when false — a present boolean attribute. Bind it (`:multiple="$flag"`) or the control silently becomes a multi-select and its array value then fails single-value normalization and is dropped without an error.

EnumFilter is multi-select by default and renders as a checkbox group; its value must be seeded as [] so Livewire collects checkboxes into a list instead of toggling one boolean. Use multiple(false) for a genuine single choice.
