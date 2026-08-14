<?php

namespace App\Support\DynamicTable\Core;

use Illuminate\Database\Eloquent\Builder;

/**
 * Constrains an already-scoped Eloquent query to rows matching a search term
 * across the given authorized+searchable columns. Implementations MUST
 * preserve every scope already applied to $query (tenancy, authorization,
 * soft-delete, etc.) — never build search on an unscoped fresh query.
 */
interface SearchDriver
{
    /**
     * @param  Column[]  $searchableColumns  Already authorization-filtered by the caller.
     */
    public function search(Builder $query, string $searchTerm, array $searchableColumns): Builder;
}
