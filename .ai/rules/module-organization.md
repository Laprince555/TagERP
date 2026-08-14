---
name: module-organization
description: Keep every business domain inside its Nwidart module while preserving the ERP hierarchy
metadata:
  type: project
  scope: "Modules/**"
  priority: high
---

# Nwidart Module Organization

## Package Boundary

The top-level ERP module is the only level implemented as an `nwidart/laravel-modules` package:

```text
Modules/{ModuleName}/
```

Examples include `Modules/Finance/`, `Modules/HR/`, and `Modules/CRM/`.

Do not create an `Apps/` directory and do not create separate Nwidart packages for SubModules, Applications, or SubApplications. Those are business hierarchy levels inside their owning module package.

Before generating a module file, inspect `config/modules.php` and use the relevant `php artisan module:make-*` command when available. Respect the configured generator paths and the module's existing siblings; do not invent a second package layout.

## Package Structure

Keep the Nwidart package-level directories at the module root:

```text
Modules/{ModuleName}/
|-- Config/
|-- Database/
|   |-- Factories/
|   |-- Migrations/
|   `-- Seeders/
|-- Http/
|   `-- Controllers/
|-- Livewire/
|-- Models/
|-- Providers/
|-- Resources/
|   `-- views/
|-- Routes/
|-- tests/
|-- composer.json
`-- module.json
```

Optional concerns such as Actions, Requests, Services, Policies, and Jobs must use the path configured for their generator in `config/modules.php` and match existing module conventions.

## Business Hierarchy Inside a Package

The logical hierarchy is:

```text
Module -> SubModule -> Application -> optional SubApplication
```

Represent that hierarchy below each technical concern instead of wrapping the module in an `Apps/` directory. For Finance -> General Ledger -> Journals:

```text
Modules/Finance/
|-- Models/GeneralLedger/Journals/
|   |-- Journal.php
|   `-- JournalLine.php
|-- Http/Controllers/GeneralLedger/Journals/
|   `-- JournalController.php
|-- Livewire/GeneralLedger/Journals/
|   |-- JournalIndex.php
|   `-- Lines/
|       `-- JournalLines.php
|-- Resources/views/general-ledger/journals/
|   |-- index.blade.php
|   `-- lines/
|       `-- index.blade.php
|-- Database/Migrations/
|   |-- create_journals_table.php
|   `-- create_journal_lines_table.php
`-- Routes/web.php
```

Use PascalCase for PHP directories and namespaces, kebab-case for view directories and URL segments, and snake_case for database identifiers.

Example namespaces:

```text
Modules\Finance\Models\GeneralLedger\Journals\Journal
Modules\Finance\Http\Controllers\GeneralLedger\Journals\JournalController
Modules\Finance\Livewire\GeneralLedger\Journals\Lines\JournalLines
```

## SubApplication Boundary

A SubApplication is an optional contextual feature beneath one specific Application record. It is not a peer of the parent Application and is not a standalone top-level module feature.

For example, Journal Lines belongs to a specific Journal record:

```text
Finance -> General Ledger -> Journals -> {journal} -> Lines
```

Its routes, authorization, queries, and UI must always be scoped through the parent Journal. The parent foreign key remains authoritative; a hierarchical code never replaces the database relationship.

Do not call every child model a SubApplication. A child becomes a SubApplication only when it has a distinct contextual feature boundary such as its own route, UI workflow, permission, or actions.

## Ownership Rules

1. Code used by one Module stays inside that module package.
2. Code used by one SubModule or Application stays in its matching hierarchy below the relevant technical concern.
3. Code shared by several Applications in one Module moves to a shared concern inside that Module.
4. Code moves to the root `app/` directory only when it is genuinely shared across modules or is global infrastructure.
5. Cross-module access must use an explicit public service or contract; do not import another module's internal implementation casually.
6. Keep module routes in the owning module's `Routes/` directory and use hierarchical names such as `finance.general-ledger.journals.index`.
7. Contextual SubApplication routes must bind the parent record, for example `finance.general-ledger.journals.lines.index` with a `{journal}` route parameter.

## Anti-Patterns

```text
Modules/Finance/Apps/GeneralLedger/...
Modules/Finance/Models/Modules/CRM/Customer.php
app/Models/Journal.php
Modules/Finance/Livewire/CRM/CustomerForm.php
```
