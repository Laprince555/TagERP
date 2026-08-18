---
paths:
  - 'Modules/*/Database/Migrations/**,database/migrations/**'
---

# Migrations

## Use foreignIdFor for all migration foreign keys
Every FK column in a migration must use `$table->foreignIdFor(Model::class[, 'column_name'])->constrained(...)->xOnDelete()`. Never `$table->foreignId('x_id')->constrained(...)` and never manual `unsignedBigInteger()+$table->foreign()`. Only pass the column-name arg to `foreignIdFor` when it differs from the model's default `{snake_model}_id`.

This is the owner's confirmed standing convention (overrides an earlier AI session's `foreignId()` usage). Migrations already committed before 2026-08-17 still use the old `foreignId('x_id')` style — converting those is a separate, not-yet-decided task; don't touch them incidentally while doing other work.

Exceptions found so far: `items`/`uom` tables and the `applications.parent_application_id` self-FK have no Eloquent model yet, so their FK columns stay on plain `foreignId()` until a model is created.
