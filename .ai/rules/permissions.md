---
paths:
  - 'app/**', 'Modules/**'
---

# Permissions & Data Scope Engine

A dual-engine access-control system already exists and is fully built and tested (`tests/Feature/Organization/`, `tests/Feature/HR/`). **Never build a parallel authorization mechanism** (a new middleware, a bespoke `if ($user->company_id === ...)` check, a new permissions table) — every new page/model wires into the existing engines below.

Two independent questions, two independent mechanisms — never conflate them:

| Question | Mechanism | Answers |
| --- | --- | --- |
| "Which **screens/actions** can this user open?" | Spatie permissions, auto-synced | Functional access |
| "Which **data rows** can this user see?" | `OrganizationScopeResolver` | Data scope |

A user can hold a permission and still see zero rows (wrong scope), or have full scope and still get `403` on an action (no permission). Both checks always apply together.

## 1. Functional permissions — do this for every new Application

- Run `php artisan permissions:sync` after seeding a new `modules`/`sub_modules`/`applications` row — it generates `{code}.view`, `{code}.update` (modules/submodules) and `{code}.view/create/update/delete/export/print` (applications) plus any `custom_actions` (e.g. `post`), and stamps `permission_name` automatically. **Never hand-write permission names.**
- Gate a page/action with `auth()->user()->can('{application_code}.{action}')` — the existing `RecordReferenceAccess::applicationAccessible()` / `NavigationTreeService` hooks already do this for you at the Table/RecordView/nav-tree layer; don't duplicate the check with custom logic.
- Grants come from `HR\Models\OrganizationStructure\{Department,JobTitle}` via `department_permissions`, `job_title_permissions`, `job_title_grade_permissions` (grade-and-above within one job title), and `job_title_grade_roles`. Never assign a Spatie permission/role to a `User` directly — grants are always edited on the department/job-title/grade side; `EmployeePermissionSynchronizer` is the *only* writer to `model_has_permissions`/`model_has_roles`.
- After editing grant tables directly (not through the UI/an Employee save), run `php artisan hr:permissions:reconcile` or drift will sit uncorrected until the next employee save.

## 2. Data scope — do this for every new model with `entity_id`/`branch_id`/`department_id`

- Add `use App\Models\Concerns\ScopedToOrganization;` to the model. That's the entire integration — it adds a global scope that filters by whichever of `entity_id`/`branch_id`/`department_id` columns the model actually has, intersected against the current user's resolved scope.
- Never write your own `where('entity_id', ...)` company-scoping logic. If you find yourself doing that, the model is missing the trait.
- A model with none of those columns needs no scoping — don't add the trait speculatively.
- Background jobs / console commands / seeders have no authenticated user, so the scope resolves to deny-all by default. Use `Model::withoutGlobalScope(\App\Support\Organization\OrganizationScopeConstraint::class)` explicitly for legitimate system-wide access — never make the constraint permissive instead.
- The org tree (`entities`, `branches`, `departments`) uses a materialized `path` column for descendant queries — never write a recursive CTE or an N+1 parent-walk to find descendants; use `->descendantsAndSelf()` on `Entity`/`Department`.
- `departments` is a **group-wide catalog** (no `entity_id` column) — many entities can share the same department row via the `department_entity` pivot. Never add a per-entity department row as a workaround.

## 3. The pivotal record

`Modules\HR\Models\EmployeeManagement\Employee` is the only bridge between a logged-in `User` and the org structure (`person_id` + `user_id` + `entity_id`/`branch_id`/`department_id`/`job_title_id`/`job_grade_id` + `entity_scope`/`department_scope`). A `User` with no active `Employee` row resolves to deny-all everywhere — this is intentional fail-closed behavior, not a bug to "fix" with a fallback.

`entity_scope` (`own`/`branch`/`entity`/`entity_tree`) and `department_scope` (`own`/`department`/`department_tree`/`all`) are two independent dimensions; visibility is their **intersection**, not a union — don't special-case one to imply the other.

## 4. Bypass

`Gate::before` in `AppServiceProvider` grants the `super_admin` Spatie role unconditional access to every permission check, and `OrganizationScopeResolver` separately grants it unrestricted data scope. Both checks exist independently — don't assume one covers the other when adding a new bypass path.

## 5. Where to read more

- Technical architecture, the resolver's caching/invalidation model, and the grant-table shapes: `docs/permissions/README.md` and the pages it links to.
- End-user steps for granting/revoking access to a specific person: `docs/user-guide/permissions.md`.
- Working examples of every piece above wired together for a real module: `Modules/HR/System/OrganizationStructure/*`, `Modules/HR/Models/EmployeeManagement/Employee.php`, `app/Support/Organization/*`.
