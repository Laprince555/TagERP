# Saved Views

Personal, named table configurations — distinct from automatic [preferences](preferences.md).
A saved view can capture filters, search, sort, column visibility/order, and per-page all at once.

## What a view can save

Whatever is currently on the Livewire component when `saveCurrentView()` is called:

- `search`
- `appliedFilters`
- `sorts`
- `perPage`
- `visibleColumns`
- `columnOrder`

`Table::rawState()` is the exact array captured — the same shape `TableState::normalize()`
consumes everywhere else in the engine, so applying a view reuses the identical security boundary
as live query state.

## Database schema

```
table_views
- id
- user_id            (FK, cascade on delete)
- table_key
- name
- configuration       JSON — TableState-shaped raw array
- schema_version      unsigned int
- is_default          boolean
- created_at / updated_at

UNIQUE(user_id, table_key, name)
INDEX(user_id, table_key)
```

## UI (✅ Implemented)

`resources/views/components/dynamic-table/saved-views.blade.php` renders a "Views" dropdown
(shown only when `@auth`) in the toolbar:

- Lists every saved view; clicking one applies it.
- Shows a "Default" badge next to the default view.
- Shows the currently active view's name next to the "Views" button label.
- "Set as default" / "Delete" buttons for the active view.
- A "Save current as…" input + button to create/update a view.
- Inline validation error display (e.g. empty name) without a page-level error.
- "Reset to table defaults" clears search/filters/sort/columns back to the table definition's own
  defaults and clears the active-view marker — distinct from the column-only preference reset.

```php
// Backend actions the UI calls:
$table->saveCurrentView('My View');   // create, or update in place if the name already exists
$table->applyView($viewId);
$table->deleteView($viewId);
$table->setDefaultView($viewId);
$table->resetToTableDefaults();       // search/filters/sort/columns -> definition defaults
```

- **Create/update ("rename")**: `saveCurrentView(string $name)` — `EloquentSavedTableViewStore::create()`
  uses `updateOrCreate(['user_id', 'table_key', 'name'], [...])`, so saving under an existing name
  updates that view in place rather than creating a duplicate. There is no separate "rename"
  action — saving the current state under a different name effectively creates a new view instead
  (renaming-by-resave is the supported pattern, not a distinct rename endpoint).
- **Apply**: re-normalizes the stored configuration against the *live* table definition via
  `TableState::normalize()` before overlaying it onto component state, resets pagination to page 1,
  and sets `Table::$activeViewId` (display-only — not re-validated against later drift from that
  view's original state).
- **Delete**: ownership-checked; deleting a view that happens to be the default does **not**
  auto-promote another view — the table simply falls back to its own `defaultSort()`/column
  defaults on the next mount, exactly as if no default view existed. Deleting the active view
  clears `$activeViewId`.
- **Set default**: exactly one view can be default per user/table — `setDefault()` clears
  `is_default` on every other view for that user/table inside a transaction before setting the
  target.

Verified in `tests/Feature/DynamicTable/SavedViewsUiTest.php`.

## Default views

If a user has a default view for a table, `Table::mount()` automatically calls `applyView()` on
it right after preferences are loaded — the table opens already showing that view. Verified by
`tests/Feature/DynamicTable/SavedViewsTest.php`'s
`'a default view is applied automatically when the table mounts'` test.

## Ownership & authorization

Every `SavedTableViewStore` method takes the authenticated `$user` and constrains the query by
`user_id` — a view id belonging to another user is never readable, appliable, deletable, or
promotable to default. Attempting any of those operations on someone else's view id is a silent
no-op (see `'a user cannot apply delete or default another users saved view'`).

## Handling removed or unauthorized columns/filters

Because `applyView()` runs the stored configuration through `TableState::normalize()` — the exact
same function every live request goes through — a saved view referencing a since-removed column,
a since-removed filter, or a column whose authorization was revoked is silently repaired: the
unknown keys are dropped, not crashed on. See
`'a stale view referencing a since removed filter is normalized rather than crashing'`.

## Applying a view resets pagination

`applyView()` always calls `Table::resetTablePage()` after overlaying state — a saved view never
opens on a stale page number.

## Shared views

🔮 Planned, not implemented in v1 (per spec). Every view is personal to the user who created it.
