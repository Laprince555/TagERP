<?php

namespace App\Models\Concerns;

use App\Support\Organization\OrganizationScopeConstraint;

/**
 * Opt-in for any model, in any module, carrying `entity_id`/`branch_id`/
 * `department_id`. Adding this trait is the entire integration — the
 * filtering logic itself lives once in OrganizationScopeConstraint so no
 * module reimplements it.
 *
 * @see .ai/rules/performance-security.md
 */
trait ScopedToOrganization
{
    public static function bootScopedToOrganization(): void
    {
        static::addGlobalScope(new OrganizationScopeConstraint);
    }
}
