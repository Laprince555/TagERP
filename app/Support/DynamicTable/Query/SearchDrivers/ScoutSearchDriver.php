<?php

namespace App\Support\DynamicTable\Query\SearchDrivers;

use App\Support\DynamicTable\Core\Exceptions\ModelNotSearchableException;
use App\Support\DynamicTable\Core\SearchDriver;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Scout\Searchable;

/**
 * Scout-backed search. Never queries Scout's own unscoped index query
 * directly — it only asks Scout for matching primary keys (bounded to
 * MAX_MATCHED_IDS), then constrains the ALREADY-SCOPED $query with
 * whereIn() on those keys. This guarantees tenancy, authorization, and
 * every other scope on $query still applies: Scout can only narrow the
 * result set, never widen it past the base query.
 *
 * A Scout index (toSearchableArray()) can legitimately include fields the
 * table definition never declared ->searchable() on — e.g. an internal
 * field indexed for a different feature. Trusting Scout's match alone would
 * let a search leak whether such a field contains a given value. So every
 * candidate id Scout returns is re-verified with the same authorized-column
 * LIKE match DatabaseSearchDriver uses: Scout only narrows candidates, the
 * authorized columns decide what actually matches.
 */
class ScoutSearchDriver implements SearchDriver
{
    /**
     * Upper bound on how many candidate ids are ever pulled from the Scout
     * index / bound into the whereIn() — prevents an unbounded index scan
     * or an oversized SQL statement for a broad search term.
     */
    public const MAX_MATCHED_IDS = 500;

    public function search(Builder $query, string $searchTerm, array $searchableColumns): Builder
    {
        $model = $query->getModel();

        if (! in_array(Searchable::class, class_uses_recursive($model::class), true)) {
            throw ModelNotSearchableException::forModel($model::class);
        }

        $ids = $model::search($searchTerm)->take(self::MAX_MATCHED_IDS)->keys();

        $query->whereIn($model->getQualifiedKeyName(), $ids);

        return (new DatabaseSearchDriver)->search($query, $searchTerm, $searchableColumns);
    }
}
