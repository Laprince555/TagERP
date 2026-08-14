# Content Blocks

Every content block extends `App\Support\DynamicRecordView\Core\Content\Content`.
There is **deliberately no raw-HTML content type** — everything renders
through one of these typed subclasses, and field values are escaped by
default (see [fields.md](fields.md)).

| Class | Purpose |
|---|---|
| `FieldsContent` | A labeled grid of typed read-only fields ("Basic Information"). `->heading()`, `->fields([...])`. |
| `TableContent` | Embeds an existing Dynamic Table for the current record via `->table($tableClass)` + `->forRelation(fn ($record) => ...)`. See [embedded-tables.md](embedded-tables.md). |
| `SubApplicationContent` | Carries a list of `SubApplication`s. Available as a building block, though `DynamicRecordView::otherDataSection()` uses `TableContent` per tab directly rather than this — see [relations.md](relations.md). |
| `EmptyStateContent` | `->message()`, `->icon()` — a placeholder block. |

All content blocks support `->visible(bool|callable $condition)`, checked as
`$content->isVisible($record)` before rendering (see
`record-view.blade.php`'s `@continue(! $content->isVisible($record))`).
