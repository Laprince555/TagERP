---
paths:
  - 'Modules/*/Livewire/**/*.php,Modules/*/System/**/*.php,app/Support/DynamicTable/**,app/Support/DynamicRecordView/**'
---

# Support Dynamic Record View

## Use RecordReferenceProvider for relation display in tables/views, not bespoke columns
1. Any relation whose target model is an Application (has APPLICATION_CODE + a registered RecordReferenceProvider) must be displayed via `RecordReferenceColumn` (DynamicTable) or `RecordReferenceViewField` (DynamicRecordView) — never `TextColumn`/`TextViewField` reading a relation attribute, and never hand-rolled blade/HTML for a "card"/"chip". The `<x-record-reference.card|tag|icon>` components already render these. Example: `Modules/Finance/Livewire/CashAndBanks/BanksTable.php` (`entity`, `category` columns).

2. A relation to a model that is NOT a registered Application (plain lookup table, e.g. currency in some contexts) uses `RelationColumn::make('currency.code')` (dot-path) instead. Do not invent a RecordReferenceProvider just to display a lookup value.

3. Before wiring any new relation display, check `app/Support/RecordReference/RecordReferenceRegistry.php` registrations (grep `RecordReferenceRegistry::register` in `Modules/*/Providers/*ServiceProvider.php`) — reuse an existing provider's `applicationCode()` instead of inventing a new lookup path.

4. Building a new Application-level entity always includes its `RecordReferenceProvider` alongside Model/Table/RecordView/Form, registered in the module ServiceProvider's `boot()` — not bolted on later once something else needs to reference it. See `Modules/Finance/System/GeneralLedger/CostCenterRecordReferenceProvider.php` + `CostCenterRecordView.php` for the full pattern (provider + `RecordReferenceViewField::make('parent')->applicationCode(...)->relation('parent')`).
