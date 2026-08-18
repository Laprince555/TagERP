---
paths:
  - 'Modules/*/Models/**,app/Support/Code/**,Modules/*/Database/Seeders/**'
---

# Seeders

## Every line/sub-application type must be registered in sub_applications
There is a `sub_applications` table (application_id FK → applications, unique `code`), separate from `applications`. Any repeating-row "line" type under a parent record (JournalLine, ApInvoiceLine/TaxLine/DeductionLine, PaymentDisbursementDetail, CollectionDetail, CycleCountLine, WarehouseLocation, ...) must have a matching row there, seeded via that module's `Database/Seeders/**/SubApplicationsSeeder.php` (raw `DB::table('sub_applications')->insertOrIgnore(...)`, same shape as `ApplicationsSeeder`), wired into `database/seeders/DatabaseSeeder.php`.

`code` = `{parentApplicationCode}-{lineCodeSlug}` (e.g. `fin-gl-jou-lin`). Line tables carry a nullable `sub_application_id` FK, auto-resolved via `SubApplication::where('code', $parent::APPLICATION_CODE.'-'.$line->lineCodeSlug())` — models using `App\Support\Code\HasAutoLineCode` get this for free in `bootHasAutoLineCode()`; models with a custom code-gen `booted()` hook (WarehouseLocation, CycleCountLine) must resolve it manually the same way.

`applications.parent_application_id` was removed (dropped in migration `2026_08_18_110001_...`) — it was an unused self-FK; do not reintroduce it. Sub-applications are never rows in `applications`.

Reminder: SQLite (used in tests) requires `dropForeign(['column_name'])` (array form), not the constraint name string, or migrations fail in the test suite.
