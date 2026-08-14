<?php

namespace App\Support\DynamicTable\Query\SearchDrivers;

use App\Support\DynamicTable\Core\Columns\RelationColumn;
use App\Support\DynamicTable\Core\SearchDriver;
use Illuminate\Database\Eloquent\Builder;

/**
 * The default search driver: plain SQL LIKE across searchable columns,
 * grouped inside one nested where() so it can never widen the base query's
 * scopes. This is exactly the logic TableQueryBuilder::applySearch() used to
 * inline directly — extracted so a different driver (e.g. Scout) can be
 * swapped in per table via Table::searchDriver().
 */
class DatabaseSearchDriver implements SearchDriver
{
    public function search(Builder $query, string $searchTerm, array $searchableColumns): Builder
    {
        $escaped = addcslashes($searchTerm, '\\%_');

        $query->where(function (Builder $q) use ($searchableColumns, $escaped): void {
            foreach ($searchableColumns as $column) {
                if ($column instanceof RelationColumn) {
                    $q->orWhereHas($column->getRelationPath(), function (Builder $relationQuery) use ($column, $escaped): void {
                        $relationQuery->where(function (Builder $inner) use ($column, $escaped): void {
                            $inner->where($column->getRelationField(), 'like', "%{$escaped}%");

                            foreach ($column->getSearchableFields() as $extraField) {
                                $inner->orWhere($extraField, 'like', "%{$escaped}%");
                            }
                        });
                    });

                    continue;
                }

                $q->orWhere($column->getField(), 'like', "%{$escaped}%");

                foreach ($column->getSearchableFields() as $extraField) {
                    $q->orWhere($extraField, 'like', "%{$escaped}%");
                }
            }
        });

        return $query;
    }
}
