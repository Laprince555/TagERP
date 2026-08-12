---
name: module-organization
description: Strict module-based file organization - all features stay within their module scope
metadata:
  type: project
  scope: "Modules/**"
  priority: high
---

# Module Organization Rule

**Applies to:** All files within `Modules/` directory

## The Rule

**Every file that belongs to a specific module MUST be placed inside that module's directory structure.**

If a feature, component, model, or service is used by a single module, it lives in that module. If it's used across multiple modules or is truly global, it belongs in `app/` or `app/Models/`.

### Directory Structure

```
Modules/{ModuleName}/
├── Apps/
│   ├── {SubModuleName}/
│   │   ├── Models/
│   │   ├── Controllers/
│   │   ├── Livewire/
│   │   ├── Views/
│   │   ├── Database/Migrations/
│   │   └── Database/Seeders/
│   └── {AnotherSubModule}/
├── Models/
├── Controllers/
├── Livewire/
├── Services/
├── Database/
├── Routes/
├── Providers/
└── Resources/views/
```

### Naming Convention

**All directory names use PascalCase (UpperCamelCase):**

| Item | Example | Note |
|---|---|---|
| Module Name | `Finance`, `CRM`, `HR` | PascalCase |
| App/SubModule | `GeneralLedger`, `AccountsPayable` | PascalCase |
| Model | `Invoice`, `JournalEntry` | PascalCase |
| Controller | `InvoiceController` | PascalCase + Controller |
| Livewire Component | `CreateInvoice.php` | PascalCase |
| Database Table | `invoices` | snake_case |
| Migration | `create_invoices_table.php` | snake_case |
| Service | `InvoiceService.php` | PascalCase + Service |
| Route File | `web.php` | lowercase |

### Examples

**Finance Module - General Ledger Sub-App:**
```
Modules/Finance/Apps/GeneralLedger/
├── Models/
│   ├── JournalEntry.php
│   └── Account.php
├── Controllers/
│   └── JournalController.php
├── Livewire/
│   └── CreateJournalEntry.php
├── Services/
│   └── JournalService.php
├── Database/Migrations/
│   └── create_journal_entries_table.php
└── Views/
    └── journal/
        ├── index.blade.php
        └── create.blade.php
```

**CRM Module - Customer Sub-App:**
```
Modules/CRM/Apps/Customer/
├── Models/
│   ├── Customer.php
│   └── Contact.php
├── Livewire/
│   └── CustomerForm.php
├── Services/
│   └── CustomerService.php
└── Views/
    └── customer/
```

### Rules

1. **One Module = One Concern**: Each module handles one business domain
2. **Nested Apps**: For complex modules, use `Apps/{SubModuleName}/` for logical grouping
3. **No Cross-App File Sharing**: If a file is needed by multiple apps, move it to the module root or `app/`
4. **Imports Use Full Namespace**: `Modules\Finance\Apps\GeneralLedger\Models\JournalEntry`
5. **Routes Are Namespaced**: `Route::name('finance.gl.journals.index')`

### When Files Belong in `app/` Instead

```
✓ Truly shared across multiple modules
✓ Global system utilities
✓ Authentication logic (already in app/Providers/FortifyServiceProvider.php)
✓ Base classes used by multiple modules
✓ Global middleware

Examples:
- app/Models/User.php (used by all modules)
- app/Traits/LogsActivity.php (used by all modules)
- app/Http/Middleware/SetUserTheme.php (global)
```

### Anti-Patterns (❌ Don't Do)

```
❌ Modules/Finance/Models/Modules/CRM/Customer.php
❌ app/Models/JournalEntry.php (belongs in Finance module)
❌ Modules/Finance/Livewire/CRM/CustomerForm.php (CRM feature in Finance folder)
❌ Mixing PascalCase and snake_case in directory names
```

---

## Why This Rule

- **Modularity**: Clear ownership and boundaries
- **Scalability**: Easy to add new modules without conflicts
- **Maintenance**: Features stay together, easier to refactor
- **Team Clarity**: Everyone knows where to find things
- **Reusability**: Modules can be extracted/shared between projects
