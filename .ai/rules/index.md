# TagERP Project Rules Index

This file maps project rules by scope. All agents and teammates must read and follow relevant rules before writing code.

## Core Architecture Rules

| Glob Pattern | Rule File | Purpose |
|---|---|---|
| `Modules/**` | [module-organization.md](module-organization.md) | Module structure, naming, file placement |
| `app/**`, `Modules/**` | [performance-security.md](performance-security.md) | Performance optimization & security priorities |
| `database/migrations`, `database/seeders` | [code-field-hierarchy.md](code-field-hierarchy.md) | Hierarchical code field for all records |

---

## How Rules Work

1. **Before Writing Code**: Read rules matching your file paths
2. **During Development**: Follow naming conventions, patterns, and constraints
3. **Record New Rules**: Use `record-rule` when establishing new team patterns

## Recorded By

- Initial rules: Project Setup (2026-08-12)
