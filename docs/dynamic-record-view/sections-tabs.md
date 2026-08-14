# Sections and Tabs

`App\Support\DynamicRecordView\Core\RecordSection` is a generic, named
container of `RecordTab`s. `DynamicRecordView` builds exactly two:
`primarySection()` (key `primary`) and `otherDataSection()` (key
`other-data`) — see [architecture.md](architecture.md).

## RecordTab

```php
RecordTab::make('overview')
    ->label('Overview')     // defaults to Str::headline($key)
    ->default()              // at most one default() tab per section
    ->visible(fn ($record) => true) // bool or callable(mixed $record): bool
    ->contents([...]);        // Content[] — see content-blocks.md
```

- `RecordSection::tabs()` throws
  `Exceptions\DuplicateTabKeyException` on a repeated key and
  `Exceptions\MultipleDefaultTabsException` if more than one tab calls
  `default()`.
- `RecordSection::defaultTabKey($record)` returns the explicit `default()`
  tab's key if it's currently authorized, otherwise the first authorized
  tab's key, otherwise throws
  `Exceptions\NoAuthorizedDefaultTabException` — a section with tabs must
  always have somewhere to land.
- `RecordSection::authorizedTabs($record)` filters by `RecordTab::isVisible($record)`.

`RecordTab::contents()` throws `Exceptions\DuplicateContentKeyException` on a
repeated content key.

## Active-tab normalization

`RecordView::setActiveTab()` and `OtherData::setActiveTab()` are the only
entry points that accept a browser-supplied tab key. Neither trusts it
directly — both pass it through
`RecordSection::normalizeActiveTabKey($candidate, $record)`, which:

- rejects a candidate longer than `RecordSection::MAX_TAB_KEY_LENGTH` (100
  chars) before even attempting a lookup;
- accepts the candidate only if it names a tab that both exists in the
  section and is currently authorized (`RecordTab::isVisible($record)`);
- otherwise falls back to `defaultTabKey($record)` — the explicit `default()`
  tab if authorized, else the first authorized tab.

This means an unknown key, an oversized string, or a key naming a real but
currently-unauthorized tab all fall back safely to the default tab rather
than erroring or leaving stale state. See the "A4: active-tab normalization"
tests in `tests/Feature/DynamicRecordView/SubModuleRecordViewTest.php`.
