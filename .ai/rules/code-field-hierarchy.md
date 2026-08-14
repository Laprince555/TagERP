---
name: code-field-hierarchy
description: Build immutable business codes from the full ERP hierarchy and parent record context
metadata:
  type: project
  scope: "database/migrations/**,database/seeders/**,Modules/**"
  priority: high
---

# Hierarchical Business Code

## Scope

Every ERP navigation entity and business record governed by the module hierarchy must have a deterministic `code`. Framework and technical tables such as jobs, cache, sessions, and pivots are excluded unless a separate requirement gives them a business code.

The relational foreign keys are the source of truth. A `code` is a stable business identifier and navigation aid; it does not replace foreign keys or authorization checks.

## Canonical Hierarchy

```text
Module -> SubModule -> Application -> Application Record -> optional SubApplication -> optional SubApplication Record
```

Use lowercase kebab-case slugs. Each stored full code contains every preceding hierarchy segment without gaps.

Let:

```text
M   = module slug
SM  = submodule slug
A   = application slug
AR  = application record slug
SA  = subapplication slug
SAR = subapplication record slug
```

The canonical formats are:

| Entity | Full code |
| --- | --- |
| Module | `{M}` |
| SubModule | `{M}-{SM}` |
| Application | `{M}-{SM}-{A}` |
| Application record | `{M}-{SM}-{A}-{AR}` |
| SubApplication definition | `{M}-{SM}-{A}-{SA}` |
| SubApplication context for a parent record | `{M}-{SM}-{A}-{AR}-{SA}` |
| SubApplication record | `{M}-{SM}-{A}-{AR}-{SA}-{SAR}` |

The SubApplication definition belongs to the Application. A usable SubApplication instance is contextual to one Application record, so the parent record slug must appear before the SubApplication slug.

## Finance Example

For Finance -> General Ledger -> Journals -> a specific Journal -> Lines:

| Entity | Local slug | Full code |
| --- | --- | --- |
| Finance Module | `fin` | `fin` |
| General Ledger SubModule | `gl` | `fin-gl` |
| Journals Application | `jou` | `fin-gl-jou` |
| Specific Journal | `jv-2026-0001` | `fin-gl-jou-jv-2026-0001` |
| Lines SubApplication definition | `lines` | `fin-gl-jou-lines` |
| Lines context for that Journal | `lines` | `fin-gl-jou-jv-2026-0001-lines` |
| Specific Journal Line | `line-0001` | `fin-gl-jou-jv-2026-0001-lines-line-0001` |

If an Application has no SubApplications, its record code stops after the Application record slug:

```text
fin-gl-jou-jv-2026-0001
```

Do not produce this for a contextual Journal Line:

```text
fin-gl-jou-lines-line-0001
```

It is invalid because it omits the parent Journal record slug.

## Persistence Rules

1. Build codes from persisted canonical slugs, never translated display names.
2. Codes are immutable after creation except through an explicit data-correction process.
3. Use a unique constraint appropriate to the owning table or business scope. A unique constraint is already an index; do not add a redundant normal index to the same column.
4. A child record must store its parent foreign key even when its code embeds the parent path.
5. Validate that each provided segment belongs to the preceding entity before generating a code.
6. Generate codes through one shared builder or service rather than hand-concatenating strings throughout the codebase. Match the project's dependency-injection conventions when implementing it.
7. Record slugs must be stable and unique within their parent scope.
8. Renaming a display label must not change the code.

## Ordering Rule

The order is always parent before child:

```text
module-submodule-application-applicationRecord-subapplication-subapplicationRecord
```

For the Journal example, the required order is therefore:

```text
fin-gl-jou-{journalSlug}-lines-{lineSlug}
```
