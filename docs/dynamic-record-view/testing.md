# Testing

- `tests/Unit/DynamicRecordView/CoreTest.php` — Core layer in isolation:
  fields, content blocks, sections/tabs, duplicate/validation exceptions,
  `RelationshipActions`/`RelationPicker` inertness.
- `tests/Feature/DynamicRecordView/ArchitectureTest.php` — Core/Resolution
  never depend on `Modules\*`, `App\Models`, Livewire, or Blade.
- `tests/Feature/DynamicRecordView/RecordResolutionTest.php` — authorized
  resolution, 404-not-leak-existence, per-request memoization.
- `tests/Feature/DynamicRecordView/SubModuleRecordViewTest.php` — the real
  `SubModuleRecordView` route and Livewire components: primary tab
  switching, Other Data independence, inactive-tab-doesn't-query, relation
  constraint can't be escaped via filters/search, two embedded instances
  don't share state, constant query count regardless of related-row count.

Query-count assertions follow the existing `DB::enableQueryLog()` /
`DB::getQueryLog()` convention already used in
`tests/Feature/DynamicTable/PerformanceRegressionTest.php`.
