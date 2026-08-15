# Testing

36 Pest tests across four files, all green. This page maps each proven claim to where it's
verified — use it to find the right file before adding a new scenario, and keep it honest: a row
here with no matching test is a claim, not a fact.

## `tests/Feature/Organization/OrganizationScopeTest.php` (13 tests)

The data-scope engine, `entity_scope` × `department_scope` as an intersection:

- `entity_tree` sees the entity and every descendant.
- `entity` (single company) does not see the parent entity.
- `branch` on a non-main branch is confined to that branch, not the whole entity.
- `department_tree` sees a department and its sub-departments.
- A child-entity manager does not gain scope over the parent, **even sharing the same
  department catalog entry** — the test that proves isolation comes from the entity dimension,
  not from a separate department row per company.
- `own` on both dimensions denies visibility into every other record.
- A terminated employee loses visibility immediately, not at next login.
- A user with no active employee record sees nothing (fail-closed).
- `super_admin` bypasses scope entirely, including with zero employee record of their own
  (the bootstrap-deadlock case).
- `status = suspended` denies scope exactly like `terminated`.
- An `entity_tree` employee whose own entity was soft-deleted denies-all instead of crashing
  (guards `->entity` being `null` before calling `->descendantsAndSelf()`).
- Reinstating a terminated employee restores visibility on the very next query.

## `tests/Feature/Organization/OrganizationVersionCacheTest.php` (4 tests) {#cache-invalidation}

- A cached scope goes stale the instant the org tree changes — no logout/login, no manual
  cache clear.
- One version bump invalidates every cached user scope simultaneously, not just the actor who
  triggered the write.
- Reads with no intervening write keep serving the same cache key (`rememberForever` isn't
  silently thrashed by an accidental bump on every read).
- A deny-all cached scope (no employee record) is invalidated by a tree change the same as any
  other — a user hired right after such a change isn't stuck deny-all.

## `tests/Feature/Organization/EmployeePermissionSynchronizerTest.php` (8 tests)

The functional-permissions engine and its grant tables:

- A job-title grant applies to every grade holding that title.
- A grade-gated grant is withheld below its threshold grade, held once the employee reaches it.
- A department grant reaches everyone in that department regardless of job title.
- Someone outside the department does not gain its module access.
- A named role attached to a job title is assigned to whoever holds it.
- Terminating an employee immediately strips their permissions and roles.
- `hr:permissions:reconcile` detects and fixes drift from a grant added directly to a grant
  table, bypassing the synchronizer.

## `tests/Feature/Organization/FunctionalPermissionScopeTest.php` (4 tests)

Scenario 6 — functional permissions and data scope are independent axes:

- A user with maximal data scope (`entity_tree` + `department_tree`) is still denied a
  permission with no grant — organizational rank has no bearing on functional access.
- A module with no grant is absent from the navigation tree entirely, not just blocked at the
  route.
- Granting the department a permission flips `can()` without touching the employee row at all.
- A permission-only grant (no role change) still invalidates the cached navigation tree — the
  regression test for the cache bug described in
  [functional-permissions.md](functional-permissions.md#employeepermissionsynchronizer).

## `tests/Feature/HR/OrganizationStructureScreensTest.php` (7 tests)

Smoke coverage that the actual Livewire screens render, not just the underlying engine:

- Index and show pages return `200` for each of Entities, Branches, Departments, Job Grades,
  Job Titles, Employees — including the embedded "Companies" (Department) and "Grades"
  (JobTitle) sub-application tabs.
- A user with no grant to a permission-gated Application is blocked (`404`) from both its
  index and show routes.

## Fixture helpers worth knowing about

Defined in `OrganizationScopeTest.php`, reused across the other three Organization test files
via Pest's global function scoping:

- `makeOrgTree()` — builds A (holding) → B → C, each with a main branch, a Finance→Payroll
  department chain attached to A.
- `makeEmployeeUser(array $attributes)` — an `Employee` with a real `User` attached.
- `plantEmployee(array $attributes)` — an `Employee` with no `User` (a "colleague" fixture).
  Both helpers auto-fill a consistent `job_title_id`/`job_grade_id` for the given
  `department_id` when the caller doesn't supply one, so fixtures satisfy
  `Employee::assertOrganizationallyConsistent()` (branch↔entity, job title↔department, job
  grade↔job title) without every test call site having to build that chain by hand.
