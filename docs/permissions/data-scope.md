# Data Scope Engine

Answers "which rows can this user see", independent of whether they're allowed to open the
screen at all (that's [functional-permissions.md](functional-permissions.md)).

## The org tree

`Modules/HR/Models/OrganizationStructure/`:

- **`Entity`** — a company you actually administer (not every row in `companies` — that table
  also holds vendors/customers). `parent_entity_id` + a materialized `path` column
  (`"/1/4/9/"`) let `->descendantsAndSelf()` find every entity at or below one, in a single
  indexed `LIKE` query — never a recursive CTE, never an N+1 parent-walk.
- **`Branch`** — belongs to one `Entity`; at most one `is_main = true` per entity (enforced in
  `Branch::booted()`, not the database — MySQL has no partial unique index).
- **`Department`** — a **group-wide catalog entry** (see below), not one row per company.
  Same materialized-path tree pattern as `Entity`.
- **`JobTitle`** / **`JobGrade`** — group-wide, not per-entity. `JobGrade.level` is a single
  numeric ladder shared by every job title (via `job_title_grade`), so "this grade or above" is
  always evaluated within one job title's own allowed grades — a senior in one title is never
  compared against a senior in another.

### Departments are a shared catalog

`departments` has **no `entity_id` column**. "Finance" is one row, shared by every entity that
uses it. Which entities/branches actually have a department active lives in the
`department_entity` pivot (`department_id`, `entity_id`, `branch_id` nullable — null means the
whole entity). This is deliberate: attaching a department to entity data-scope isolation come
from different places, and conflating them is the mistake this shape avoids — see
["a finance manager in a child entity does not gain scope over the parent, even sharing the
same department catalog entry"](testing.md) for the test that proves it.

## The pivotal record: `Employee`

`Modules\HR\Models\EmployeeManagement\Employee` is the **only** bridge between a logged-in
`User` and the org structure:

```
employees: person_id, user_id (nullable), entity_id, branch_id, department_id,
           job_title_id, job_grade_id, entity_scope, department_scope, status, ...
```

`user_id` is nullable — not every employee logs in (e.g. a factory worker). A `User` with no
active (`status = 'active'`) `Employee` row resolves to **deny-all**, everywhere, by design —
this is not a bug to patch around with a fallback.

## Two independent scope dimensions

| Column | Values | Answers |
|---|---|---|
| `entity_scope` | `own` \| `branch` \| `entity` \| `entity_tree` | Which companies/branches (horizontal) |
| `department_scope` | `own` \| `department` \| `department_tree` \| `all` | Which slice of the org chart (vertical) |

Visible rows = the **intersection** of both, never a union. This is what lets one mechanism
express every case discussed while designing this system:

- A group-level finance manager (`entity_tree` + `department_tree`) sees Finance across the
  parent entity and every descendant.
- The same manager, stationed at one child entity instead (`entity` + `department_tree`), sees
  Finance in that one company only — never the parent, never siblings.
- An employee on a non-main branch (`branch`) is confined to that branch, even for other
  records in the very same entity.
- `own` is **not** "their branch" — it's zero entity/branch broadening at all. Self-service
  scoping (an employee seeing their own leave requests) is a different dimension entirely
  (filtering by `employee_id`), never conflated with `branch`.

`department_scope = department_tree` walks the department's own materialized path — entity-
agnostic, unaffected by which entities share that department row.

## `OrganizationScopeResolver`

```php
$scope = app(OrganizationScopeResolver::class)->resolve($user); // OrganizationScope
```

Returns an `OrganizationScope` value object: `entityIds`, `branchIds`, `departmentIds`, each
either `array<int>` (a concrete allow-list) or `null` (**unrestricted** — reserved for
`super_admin`). An **empty array** (`[]`) means deny-all — the distinction between `null` and
`[]` is the entire authorization boundary; never collapse it.

Resolution order:

1. `super_admin` role → `OrganizationScope::unrestricted()`, no further lookup.
2. No active `Employee` row for this user → `OrganizationScope::denyAll()`.
3. Otherwise, resolve `entity_scope` → `[entityIds, branchIds]` and `department_scope` →
   `departmentIds` independently, per the table above.

`explain(User $user): array{scope, trace}` runs the same resolution **uncached**, annotated
with the reasoning at each step — the tool for "why can't this user see that record", not for
the request hot path.

### Caching & invalidation

`resolve()` is cached under `org_scope:{user_id}:v{version}`, `rememberForever`.
`OrganizationVersion` is a single global counter — bumping it once (via `Entity`, `Branch`,
`Department`, or `Employee`'s `saved`/`deleted` hooks) logically invalidates **every** user's
cached scope at the same instant, rather than hunting down which users a tree edit affected.
Reads with no intervening write never bump the counter (see `testing.md` for the anti-thrash
test) — `rememberForever` genuinely never re-queries until something actually changed.

## `ScopedToOrganization` — the integration point

```php
use App\Models\Concerns\ScopedToOrganization;

class Invoice extends Model
{
    use ScopedToOrganization;
}
```

That's the entire integration for any model, in any module. The trait adds a global scope
(`App\Support\Organization\OrganizationScopeConstraint`) that inspects which of
`entity_id`/`branch_id`/`department_id` the model's table actually has (cached per-table column
listing, not re-queried every row) and filters by the intersection above — a model with only
`entity_id` is untouched by the department dimension, and vice versa.

No authenticated user (a queue worker, a console command, a scheduled job) → the constraint
fails closed (`whereRaw('1 = 0')`). Legitimate system-wide access must opt out explicitly:

```php
Employee::withoutGlobalScope(OrganizationScopeConstraint::class)->...
```

`OrganizationScopeResolver` itself does exactly this internally when looking up the *current*
user's own `Employee` row — resolving scope must never depend on a scope that is itself being
computed from that same row, or every lookup would recurse forever.

## Known ceilings (deliberate, not forgotten)

- **Regional/multi-branch grants** beyond one `entity_scope` value — e.g. a manager over
  exactly 3 named branches — needs an additive `hr_employee_scope_grants` table. Not built;
  `entity_scope` alone covers everything currently required.
- **Field-level visibility** (e.g. hide a project's cost figure from an engineer, show it to
  the project manager) is not a new mechanism — it's `Column::visible()` /
  `ViewField` visibility callbacks the Dynamic Table/Record View engines already support,
  gated by the *functional* permission for that specific field's action.
