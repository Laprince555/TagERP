# Defining a Record View

Extend `App\Support\DynamicRecordView\Core\DynamicRecordView` and implement:

| Method | Purpose |
|---|---|
| `model(): string` | The `class-string<Model>` this view is for. |
| `query(): Builder` | The **authorized** base query records are resolved through — see [record-resolution.md](record-resolution.md). Never bypassed. |
| `title(mixed $record): string` | Header title. |
| `subtitle(mixed $record): ?string` | Optional header subtitle. Default `null`. |
| `tabs(): array` | `RecordTab[]` for the Primary section. Default `[]`. |
| `subApplications(): array` | `SubApplication[]` for the Other Data section. Default `[]`. |

Also set `protected string $viewKey` — required (throws
`App\Support\DynamicRecordView\Core\Exceptions\MissingViewKeyException` if
left empty), and used to namespace embedded-table instance keys (see
[embedded-tables.md](embedded-tables.md)).

`primarySection()` and `otherDataSection()` are built for you from `tabs()`
and `subApplications()` — you normally never call them directly except in
tests.

See `Modules\General\System\SubModuleRecordView` for a complete real example.
