<?php

namespace App\Support\Organization;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Schema;

/**
 * Applies the intersection of a user's entity/branch/department scope to any
 * model carrying the matching columns. Only the columns actually present on
 * the model are filtered — a model with just `entity_id` is untouched by the
 * department dimension, and vice versa.
 */
class OrganizationScopeConstraint implements Scope
{
    /** @var array<string, array<int, string>> */
    private static array $columnCache = [];

    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();

        // No authenticated actor (a queue worker, a console command, a
        // scheduled job): fail closed. Callers that legitimately need
        // system-wide access must opt out explicitly via
        // withoutGlobalScope(OrganizationScopeConstraint::class).
        if (! $user) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $scope = app(OrganizationScopeResolver::class)->resolve($user);
        $table = $model->getTable();
        $columns = $this->columnsFor($table);

        if (in_array('entity_id', $columns, true) && $scope->entityIds !== null) {
            $builder->whereIn("{$table}.entity_id", $scope->entityIds);
        }

        if (in_array('branch_id', $columns, true) && $scope->branchIds !== null) {
            $builder->whereIn("{$table}.branch_id", $scope->branchIds);
        }

        if (in_array('department_id', $columns, true) && $scope->departmentIds !== null) {
            $builder->whereIn("{$table}.department_id", $scope->departmentIds);
        }
    }

    /**
     * @return array<int, string>
     */
    private function columnsFor(string $table): array
    {
        return self::$columnCache[$table] ??= Schema::getColumnListing($table);
    }
}
