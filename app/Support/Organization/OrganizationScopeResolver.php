<?php

namespace App\Support\Organization;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Modules\HR\Models\EmployeeManagement\Employee;
use Modules\HR\Models\OrganizationStructure\Branch;

/**
 * Turns a User into the set of entity/branch/department ids their queries
 * are allowed to see. Two independent dimensions are resolved and the
 * caller applies them as an intersection: entity_scope answers "which
 * companies/branches" (horizontal), department_scope answers "which slice
 * of the org chart" (vertical) — a regional finance manager needs both at
 * once without either one leaking into the other's decision.
 */
class OrganizationScopeResolver
{
    public function __construct(private readonly OrganizationVersion $version) {}

    public function resolve(User $user): OrganizationScope
    {
        $key = "org_scope:{$user->id}:v{$this->version->current()}";

        // Cache a plain array, never the OrganizationScope object itself:
        // config('cache.serializable_classes') is false in this app, so the
        // database/file cache stores unserialize with allowed_classes =
        // false and silently turn ANY cached object into
        // __PHP_Incomplete_Class on the next read — the exact failure mode
        // already documented on NavigationTreeService::getApplicationByCode().
        $cached = Cache::rememberForever($key, fn (): array => $this->resolveFresh($user)->toArray());

        // Defends against an entry written before this fix (or by any other
        // future bug that stores something else under this key): treat a
        // non-array hit as a miss and recompute, rather than fail the whole
        // request on stale cache content the version bump can't reach.
        if (! is_array($cached)) {
            $cached = $this->resolveFresh($user)->toArray();
            Cache::forever($key, $cached);
        }

        return OrganizationScope::fromArray($cached);
    }

    /**
     * Same resolution as resolve(), but never cached and annotated with the
     * reasoning at each step — for support tooling answering "why can't this
     * user see that record", not for the request hot path.
     *
     * @return array{scope: OrganizationScope, trace: array<int, string>}
     */
    public function explain(User $user): array
    {
        $trace = [];

        $scope = $this->resolveFresh($user, $trace);

        return ['scope' => $scope, 'trace' => $trace];
    }

    /**
     * @param  array<int, string>  $trace
     */
    private function resolveFresh(User $user, array &$trace = []): OrganizationScope
    {
        if ($user->hasRole('super_admin')) {
            $trace[] = "user #{$user->id} holds role super_admin — unrestricted scope";

            return OrganizationScope::unrestricted();
        }

        // Bypasses ScopedToOrganization deliberately: resolving *this* user's
        // own employee row must never depend on a scope that is itself being
        // computed from that row, or every lookup would recurse forever.
        $employee = Employee::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (! $employee) {
            $trace[] = "user #{$user->id} has no active employee record — deny all";

            return OrganizationScope::denyAll();
        }

        $trace[] = "user #{$user->id} → employee #{$employee->id} ({$employee->code}), entity_scope={$employee->entity_scope}, department_scope={$employee->department_scope}";

        [$entityIds, $branchIds] = $this->resolveEntityDimension($employee, $trace);
        $departmentIds = $this->resolveDepartmentDimension($employee, $trace);

        return new OrganizationScope($entityIds, $branchIds, $departmentIds);
    }

    /**
     * @param  array<int, string>  $trace
     * @return array{0: array<int>, 1: array<int>}
     */
    private function resolveEntityDimension(Employee $employee, array &$trace): array
    {
        return match ($employee->entity_scope) {
            // 'own' is not "their branch" — it's "no entity/branch broadening
            // at all", because self-service scoping is by employee_id, a
            // different dimension entirely. Conflating it with 'branch' would
            // let an 'own'-scoped employee see every record in their branch.
            'own' => [[], []],
            'branch' => [
                [$employee->entity_id],
                [$employee->branch_id],
            ],
            'entity' => [
                [$employee->entity_id],
                Branch::where('entity_id', $employee->entity_id)->pluck('id')->all(),
            ],
            'entity_tree' => (function () use ($employee, &$trace) {
                // entity_id can point at a soft-deleted (or otherwise
                // vanished) row; the default belongsTo excludes trashed
                // rows, so ->entity is null here rather than a stale
                // instance — deny-all instead of a null-pointer crash.
                if (! $employee->entity) {
                    $trace[] = "entity_tree → employee's entity #{$employee->entity_id} no longer resolves — deny all";

                    return [[], []];
                }

                $entityIds = $employee->entity->descendantsAndSelf()->pluck('id')->all();
                $trace[] = 'entity_tree → entities '.implode(',', $entityIds);

                return [
                    $entityIds,
                    Branch::whereIn('entity_id', $entityIds)->pluck('id')->all(),
                ];
            })(),
        };
    }

    /**
     * @param  array<int, string>  $trace
     * @return array<int>|null
     */
    private function resolveDepartmentDimension(Employee $employee, array &$trace): ?array
    {
        return match ($employee->department_scope) {
            'own' => [],
            'department' => [$employee->department_id],
            'department_tree' => $employee->department
                ? $employee->department->descendantsAndSelf()->pluck('id')->all()
                : $this->denyDepartmentTree($employee, $trace),
            'all' => null,
        };
    }

    /**
     * @param  array<int, string>  $trace
     * @return array<int>
     */
    private function denyDepartmentTree(Employee $employee, array &$trace): array
    {
        $trace[] = "department_tree → employee's department #{$employee->department_id} no longer resolves — deny all";

        return [];
    }
}
