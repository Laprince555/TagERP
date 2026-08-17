<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\HR\Models\EmployeeManagement\Employee;
use Modules\HR\Models\OrganizationStructure\JobTitle;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * The "Role" the General → Security & Roles screens manage — a named bundle
 * of permissions (e.g. "Finance Manager") assignable to a job title
 * (optionally grade-gated, via job_title_grade_roles) or directly to one
 * employee as an exception (via employee_roles). Both are grant *sources*
 * EmployeePermissionSynchronizer reads from — this class adds no write path
 * of its own, so the "one writer" rule for model_has_roles is unaffected.
 *
 * Bound as the Spatie role model in config/permission.php so every existing
 * ->assignRole()/->hasRole()/HasRoles call keeps working unmodified.
 */
class Role extends SpatieRole
{
    public function jobTitles(): BelongsToMany
    {
        return $this->belongsToMany(JobTitle::class, 'job_title_grade_roles')
            ->withPivot('job_grade_id')
            ->withTimestamps();
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'employee_roles')
            ->withTimestamps();
    }
}
