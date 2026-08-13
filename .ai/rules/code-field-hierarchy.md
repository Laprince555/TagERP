---
name: code-field-hierarchy
description: Every database record must have a hierarchical code field for navigation and identification
metadata:
  type: project
  scope: "database/migrations,database/seeders,Modules/**"
  priority: high
---

# Hierarchical Code Field Rule

**Applies to:** All tables that store records, configurations, or entities

## The Rule

**Every record inserted into the database (via migration, seeder, or programmatically) MUST include a `code` field.**

The `code` field is a hierarchical identifier that represents the full path to that record in the module structure.

### Code Format

```
module-slug-application-slug[-subapplication-slug][-record-slug]
```

**Components (left to right):**
1. `module-slug` - Module name (lowercase, kebab-case)
2. `application-slug` - Application name within module (lowercase, kebab-case)
3. `[subapplication-slug]` - Optional: Subapplication within app (lowercase, kebab-case)
4. `[record-slug]` - Optional: Individual record identifier (lowercase, kebab-case)

### Stop Points (When to Stop Adding Segments)

The `code` stops at your current hierarchy level:

| Level | Code Format | Example |
|---|---|---|
| **Module** | `{module}` | `finance` |
| **Module > Application** | `{module}-{application}` | `finance-general-ledger` |
| **Module > Application > SubApp** | `{module}-{application}-{subapp}` | `finance-general-ledger-accounts` |
| **Record in Application** | `{module}-{application}-{record-slug}` | `finance-general-ledger-jv-2024001` |
| **Record in SubApplication** | `{module}-{application}-{subapp}-{record-slug}` | `finance-general-ledger-accounts-code-1000` |

### Examples

**Finance Module:**
```
Module registration:
  code: "finance"

GeneralLedger Application:
  code: "finance-general-ledger"

Accounts SubApplication:
  code: "finance-general-ledger-accounts"

Journal Entry record:
  code: "finance-general-ledger-jv-20240812001"

Account record:
  code: "finance-general-ledger-accounts-1000"
```

**CRM Module:**
```
Module registration:
  code: "crm"

Customer Application:
  code: "crm-customer"

Customer record:
  code: "crm-customer-cust-001"
```

**HR Module:**
```
Module registration:
  code: "hr"

Employee Application:
  code: "hr-employee"

Department SubApplication:
  code: "hr-employee-department"

Employee record:
  code: "hr-employee-emp-00001"

Department record:
  code: "hr-employee-department-dept-001"
```

---

## Implementation

### Database Schema

**Every table must include:**

```php
// In migration
$table->string('code')->unique()->index();
```

**Placement:**
- Add as first column after `id`
- Make it `unique()` and `index()` for fast lookups
- Never null

### Seeder Example

```php
// ModuleSeeder.php
Module::create([
    'code' => 'finance',           // ← Just module
    'name' => 'Finance',
]);

// GeneralLedgerSeeder.php (under Finance)
Application::create([
    'code' => 'finance-general-ledger',  // ← Module-application
    'module_code' => 'finance',
    'name' => 'General Ledger',
]);

// AccountSeeder.php (under GL)
Account::create([
    'code' => 'finance-general-ledger-1000',  // ← Module-app-record
    'app_code' => 'finance-general-ledger',
    'account_number' => '1000',
    'name' => 'Cash',
]);
```

### Programmatic Creation

```php
// When creating a record programmatically
$journal = JournalEntry::create([
    'code' => 'finance-general-ledger-jv-' . date('YmdHis') . '-' . rand(1000, 9999),
    'module_code' => 'finance',
    'app_code' => 'finance-general-ledger',
    'entry_date' => now(),
    // ... other fields
]);
```

### Code Generation Helper

**Create a service for consistent code generation:**

```php
// app/Services/CodeGeneratorService.php
class CodeGeneratorService
{
    public static function generate(
        string $moduleSlug,
        ?string $appSlug = null,
        ?string $subAppSlug = null,
        ?string $recordSlug = null
    ): string {
        $parts = [$moduleSlug];
        
        if ($appSlug) $parts[] = $appSlug;
        if ($subAppSlug) $parts[] = $subAppSlug;
        if ($recordSlug) $parts[] = $recordSlug;
        
        return implode('-', $parts);
    }
}

// Usage
$code = CodeGeneratorService::generate(
    'finance',
    'general-ledger',
    null,
    'jv-2024001'
);
// Output: finance-general-ledger-jv-2024001
```

---

## Rules

1. **No Spaces**: Always kebab-case (lowercase-with-dashes)
2. **Unique**: Each code must be unique within its scope
3. **Immutable**: Once set, should never change (acts as identifier)
4. **Hierarchical**: Always represents full path
5. **Deterministic**: Same entity always gets same code
6. **No Gaps**: Don't skip levels (e.g., `finance-accounts` is wrong if it should be `finance-general-ledger-accounts`)

### Anti-Patterns (❌ Don't Do)

```php
❌ code: "Finance" // Has space/capitals
❌ code: "JV-2024001" // Missing module-app prefix
❌ code: "finance-jv-2024001" // Missing application level
❌ code: "finance-general-ledger" // For a record (should have record slug)
❌ code: "finance_general_ledger" // Uses underscore instead of dash
```

---

## Benefits

- **Navigation**: Can determine location from code alone
- **Access Control**: Permission checks based on code path
- **Hierarchical Queries**: Find all children of a module/app
- **Debugging**: Easy to trace which module/app caused an issue
- **API**: Meaningful identifiers instead of just IDs
- **User-Friendly**: Can display full path to users

---

## When to Update

The `code` field should change ONLY if:
- ❌ NEVER: Record is renamed (create new, archive old)
- ❌ NEVER: Record is moved to different module/app
- ✓ YES: Explicitly required by data correction process

Once set, treat `code` as immutable.
