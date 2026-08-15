<?php

namespace App\Support\Organization;

use App\Services\NavigationTreeService;
use Illuminate\Support\Facades\DB;
use Modules\HR\Models\EmployeeManagement\Employee;

/**
 * Computes the union of an employee's grants (department + job title +
 * job-title-and-grade-and-above) and syncs them onto the linked User as
 * Spatie permissions/roles. Spatie is the system of record at read time —
 * every `$user->can()` / `@can` / policy check keeps working unmodified —
 * this class is the only writer, so a stale assignment can only ever be a
 * sync bug, never a second source of truth to reconcile against.
 */
class EmployeePermissionSynchronizer
{
    public function __construct(private readonly NavigationTreeService $navigationTreeService) {}

    public function sync(Employee $employee): void
    {
        $employee->loadMissing('user');

        $target = $this->resolve($employee);

        $employee->user?->syncPermissions($target['permissions']);
        $employee->user?->syncRoles($target['roles']);

        // The navigation tree cache key hashes Spatie *role* names only
        // (buildCacheKey()'s roleHash), not raw permission names. A grant
        // added straight to a permission (no role involved, e.g. Scenario 6
        // above) would otherwise leave the sidebar serving a stale tree
        // until some unrelated Module/SubModule/Application write happened
        // to bump the version first.
        $this->navigationTreeService->invalidateCache();
    }

    /**
     * What this employee's grants currently compute to, without writing
     * anything — the reconcile command's dry-run diffs against this instead
     * of mutating the user just to inspect the result.
     *
     * @return array{permissions: array<int, string>, roles: array<int, string>}
     */
    public function resolve(Employee $employee): array
    {
        // `! $employee->exists` covers a force delete, where the row is gone
        // but deleted_at was never written — checking trashed() alone would
        // hand a deleted employee's user their full grant set back.
        if ($employee->status !== 'active' || ! $employee->exists || $employee->trashed()) {
            return ['permissions' => [], 'roles' => []];
        }

        return [
            'permissions' => $this->resolvePermissionNames($employee),
            'roles' => $this->resolveRoleNames($employee),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function resolvePermissionNames(Employee $employee): array
    {
        $departmentGrants = DB::table('department_permissions')
            ->join('permissions', 'permissions.id', '=', 'department_permissions.permission_id')
            ->where('department_permissions.department_id', $employee->department_id)
            ->pluck('permissions.name');

        $jobTitleGrants = DB::table('job_title_permissions')
            ->join('permissions', 'permissions.id', '=', 'job_title_permissions.permission_id')
            ->where('job_title_permissions.job_title_id', $employee->job_title_id)
            ->pluck('permissions.name');

        $gradeGrants = DB::table('job_title_grade_permissions')
            ->join('permissions', 'permissions.id', '=', 'job_title_grade_permissions.permission_id')
            ->join('job_grades', 'job_grades.id', '=', 'job_title_grade_permissions.job_grade_id')
            ->where('job_title_grade_permissions.job_title_id', $employee->job_title_id)
            ->where('job_grades.level', '<=', $employee->jobGrade->level)
            ->pluck('permissions.name');

        return $departmentGrants->merge($jobTitleGrants)->merge($gradeGrants)->unique()->values()->all();
    }

    /**
     * @return array<int, string>
     */
    private function resolveRoleNames(Employee $employee): array
    {
        $jobTitleGrants = DB::table('job_title_grade_roles')
            ->join('roles', 'roles.id', '=', 'job_title_grade_roles.role_id')
            // Left join: a null job_grade_id means "every grade of this job
            // title", and an inner join would silently drop those rows
            // before the whereNull() below ever saw them.
            ->leftJoin('job_grades', 'job_grades.id', '=', 'job_title_grade_roles.job_grade_id')
            ->where('job_title_grade_roles.job_title_id', $employee->job_title_id)
            ->where(function ($query) use ($employee): void {
                $query->whereNull('job_title_grade_roles.job_grade_id')
                    ->orWhere('job_grades.level', '<=', $employee->jobGrade->level);
            })
            ->pluck('roles.name');

        // A Rule assigned directly to this one employee — an exception on
        // top of whatever their job title already grants, not instead of it.
        $directGrants = DB::table('employee_rules')
            ->join('roles', 'roles.id', '=', 'employee_rules.role_id')
            ->where('employee_rules.employee_id', $employee->id)
            ->pluck('roles.name');

        return $jobTitleGrants->merge($directGrants)->unique()->values()->all();
    }
}
