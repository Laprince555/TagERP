# Permissions & Data Scope Engine

A dual-engine access-control system: one engine decides which **screens/actions** a user can
reach (functional permissions), a second, fully independent engine decides which **data rows**
that user can see once inside a screen (data scope). Every feature documented here is
implemented and covered by Pest tests unless explicitly marked 🔮 Planned or ❌ Unsupported —
see [testing.md](testing.md) for what's verified.

## Why two engines, not one

A single permission check answering "can this user see invoices?" cannot also answer "which
company's invoices?" — conflating the two into one mechanism is the most common way access
control collapses in an ERP (a scope bug looks like a permission bug and vice versa, and fixing
one silently breaks the other). This system keeps them structurally separate:

| Question | Mechanism | Core class |
|---|---|---|
| "Which screens/actions can this user open?" | Spatie roles/permissions, auto-synced from the org structure | `App\Support\Organization\EmployeePermissionSynchronizer` |
| "Which data rows can this user see?" | A resolved, cached set of entity/branch/department ids | `App\Support\Organization\OrganizationScopeResolver` |

Both checks apply on every request, independently. A Sales Manager can have full data-scope
visibility across the whole company and still get `can('fin-gl-jou.view') === false` — rank in
the org tree has no bearing on functional access, and a wide functional grant has no bearing on
which rows come back from a query.

## What it is

- **Org structure** (`Modules/HR/Models/OrganizationStructure/`) — `Entity`, `Branch`,
  `Department` (a group-wide catalog, not one row per company — see
  [data-scope.md](data-scope.md#departments-are-a-shared-catalog)), `JobTitle`, `JobGrade`.
  `Entity`/`Department` use a materialized `path` column for descendant queries.
- **The pivotal record** — `Modules\HR\Models\EmployeeManagement\Employee` is the only bridge
  between a logged-in `User` and the org structure. See [data-scope.md](data-scope.md).
- **Data scope engine** (`app/Support/Organization/`) — `OrganizationScopeResolver`,
  `OrganizationScope` (the resolved value object), `OrganizationVersion` (cache invalidation),
  `OrganizationScopeConstraint` (the Eloquent global scope), and the
  `App\Models\Concerns\ScopedToOrganization` trait any model opts into.
- **Functional permissions engine** — `php artisan permissions:sync` generates every Spatie
  permission from the existing `modules`/`sub_modules`/`applications` code hierarchy;
  `department_permissions` / `job_title_permissions` / `job_title_grade_permissions` /
  `job_title_grade_roles` are the grant tables; `EmployeePermissionSynchronizer` is the only
  writer to `model_has_permissions`/`model_has_roles`; `php artisan hr:permissions:reconcile`
  is the drift backstop.

## Feature matrix

| Feature | Status |
|---|---|
| Entity/Branch/Department/JobTitle/JobGrade org structure, with materialized-path trees | ✅ Implemented |
| Departments as a group-wide catalog shared across entities (`department_entity` pivot) | ✅ Implemented |
| `Employee` as the single User↔org-structure bridge | ✅ Implemented |
| Two-dimensional data scope (`entity_scope` × `department_scope`, resolved as an intersection) | ✅ Implemented |
| Cached scope resolution with single-counter invalidation (`OrganizationVersion`) | ✅ Implemented |
| `ScopedToOrganization` trait — opt-in global scope for any model, any module | ✅ Implemented |
| Fail-closed on no authenticated user / no active `Employee` row | ✅ Implemented |
| Fail-closed (not a crash) on a soft-deleted entity/department mid-tree | ✅ Implemented |
| `super_admin` bypass for both engines independently | ✅ Implemented |
| Permission auto-generation from the module/sub_module/application code hierarchy | ✅ Implemented — `permissions:sync` |
| Grant tables: department / job title / job-title-and-grade-and-above / named roles | ✅ Implemented |
| Automatic Spatie sync on employee hire/reassignment/promotion/termination | ✅ Implemented |
| Drift detection & correction (`hr:permissions:reconcile`, with `--dry-run`) | ✅ Implemented |
| Navigation tree (sidebar) filtered by functional permission at all 3 levels (module/sub_module/application) | ✅ Implemented |
| Field-level permissions (e.g. hide a cost column from some job titles) | ✅ Implemented — via `Column::visible()` / `ViewField` visibility callbacks; no new mechanism needed |
| Employee self-service (`own` scope) as a distinct data-scope value | ✅ Implemented (scope value only — the ESS submodule's screens are not yet built) |
| Department-level manager auto-inheriting scope over sub-departments | ✅ Implemented — `department_scope = department_tree` |
| Regional/multi-branch manager scope grants beyond one branch | 🔮 Planned — `hr_employee_scope_grants` (additive grant table, additive to `entity_scope`) |
| Employee assignment history (salary/job-title changes over time) | 🔮 Planned — `employee_assignment_history` |
| Approval workflows (multi-step document sign-off, exceptions) | 🔮 Planned — separate engine, deliberately deferred |
| Edit/update forms for org-structure records | ❌ Not implemented — `DynamicForm` is create-only in this pass; corrections go through direct DB access or a future update-form pass |
| UI to manage `department_entity` / `job_title_grade` attachments | ❌ Not implemented — attach via `Department::attachToEntity()` / `JobTitle::jobGrades()->attach()` directly; the record-view "Companies"/"Grades" tabs are read-only |

This matrix is maintained honestly — update it when a row's status changes, not just when a
feature ships.

## Installation

Nothing to install. Built entirely on packages already present: Laravel 13, `spatie/laravel-permission`
8.3 (previously installed, unused before this engine), Livewire 4, Pest 5. No new Composer
package was added.

## Where to go next

- [data-scope.md](data-scope.md) — the org tree, the `Employee` record, `entity_scope` /
  `department_scope`, caching and invalidation, the `ScopedToOrganization` trait.
- [functional-permissions.md](functional-permissions.md) — permission generation, the grant
  tables, the synchronizer, drift reconciliation.
- [testing.md](testing.md) — the full scenario matrix and where each is proven.
- End users: [../user-guide/permissions.md](../user-guide/permissions.md) — granting/revoking
  a person's access, in plain terms, no code.
