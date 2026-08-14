<?php

namespace App\Support\DynamicTable\Query;

use App\Support\DynamicTable\Core\Columns\ComputedColumn;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Columns\RelationColumn;
use App\Support\DynamicTable\Core\Exceptions\HasManySortWithoutAggregateException;
use App\Support\DynamicTable\Core\Exceptions\InvalidModelException;
use App\Support\DynamicTable\Core\Filter;
use App\Support\DynamicTable\Core\Filters\BelongsToFilter;
use App\Support\DynamicTable\Core\Filters\BooleanFilter;
use App\Support\DynamicTable\Core\Filters\DateFilter;
use App\Support\DynamicTable\Core\Filters\EnumFilter;
use App\Support\DynamicTable\Core\Filters\NumberFilter;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\SearchDriver;
use App\Support\DynamicTable\Core\TableDefinition;
use App\Support\DynamicTable\Core\TableState;
use App\Support\DynamicTable\Query\SearchDrivers\DatabaseSearchDriver;
use App\Support\RecordReference\RecordReferenceRegistry;
use App\Support\RecordReference\RecordReferenceVariant;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

/**
 * Converts a trusted TableDefinition + validated TableState into a real
 * Eloquent query. No queries live in Blade, and no query construction lives
 * in column/filter constructors — it all funnels through here.
 */
class TableQueryBuilder
{
    protected SearchDriver $searchDriver;

    public function __construct(protected TableDefinition $definition, ?SearchDriver $searchDriver = null)
    {
        $this->searchDriver = $searchDriver ?? new DatabaseSearchDriver;
    }

    public function query(TableState $state): Builder
    {
        $query = ($this->definition->query)();

        if (! $query instanceof Builder) {
            throw InvalidModelException::forQuery();
        }

        $this->applySelect($query, $state);
        $this->applyEagerLoads($query, $state);
        $this->applySearch($query, $state);
        $this->applyFilters($query, $state);
        $this->applySort($query, $state);

        return $query;
    }

    public function paginate(TableState $state): LengthAwarePaginator
    {
        return $this->query($state)->paginate($state->perPage, ['*'], 'page', $state->page);
    }

    public function simplePaginate(TableState $state): Paginator
    {
        return $this->query($state)->simplePaginate($state->perPage, ['*'], 'page', $state->page);
    }

    public function cursorPaginate(TableState $state): CursorPaginator
    {
        return $this->query($state)->cursorPaginate($state->perPage, ['*'], 'cursor', $state->cursor);
    }

    protected function applySelect(Builder $query, TableState $state): void
    {
        $model = $query->getModel();
        $fields = [$model->getTable().'.'.$model->getKeyName()];

        foreach ($state->orderedVisibleColumns() as $key) {
            $column = $this->definition->column($key);

            if (! $column || $column instanceof RelationColumn) {
                continue;
            }

            if ($column instanceof ComputedColumn && ! $column->hasExplicitField()) {
                continue;
            }

            if ($column instanceof RecordReferenceColumn) {
                // Referencing the row itself: select only the provider-declared
                // identity columns, plus card facts when the card variant is
                // visible. Never select preview-only columns here.
                if ($column->getRelationPath() !== null) {
                    continue;
                }

                $provider = app(RecordReferenceRegistry::class)->resolve($column->getApplicationCode());
                if ($provider) {
                    $modelClass = get_class($model);
                    if (! is_a($modelClass, $provider->modelClass(), true)) {
                        throw new InvalidModelException("Record reference column [{$column->getKey()}] points to [{$modelClass}], expected [{$provider->modelClass()}].");
                    }
                }
                $wanted = $provider
                    ? array_merge($provider->identityColumns(), $column->getVariant() === RecordReferenceVariant::Card ? $provider->cardColumns() : [])
                    : [];

                foreach ($wanted as $wantedField) {
                    $fields[] = $model->getTable().'.'.$wantedField;
                }

                continue;
            }

            $fields[] = $model->getTable().'.'.$column->getField();
        }

        // Include the FK for any visible first-level belongsTo relation column so eager loading can match rows.
        foreach ($state->orderedVisibleColumns() as $key) {
            $column = $this->definition->column($key);

            $relationPath = match (true) {
                $column instanceof RelationColumn => $column->getRelationPath(),
                $column instanceof RecordReferenceColumn => $column->getRelationPath(),
                default => null,
            };

            if ($relationPath === null) {
                continue;
            }

            $relation = $this->firstSegmentRelation($model, $relationPath);

            if ($relation instanceof BelongsTo) {
                $fields[] = $model->getTable().'.'.$relation->getForeignKeyName();
            }
        }

        $query->select(array_values(array_unique($fields)));
    }

    protected function applyEagerLoads(Builder $query, TableState $state): void
    {
        $relationColumnFields = [];
        $recordRefColumns = [];
        $model = $query->getModel();

        foreach ($state->orderedVisibleColumns() as $key) {
            $column = $this->definition->column($key);

            if ($column instanceof RelationColumn) {
                $relationColumnFields[$column->getRelationPath()][] = $column->getRelationField();
            }

            if ($column instanceof RecordReferenceColumn && $column->getRelationPath() !== null) {
                $recordRefColumns[$column->getRelationPath()][] = $column;
            }
        }

        $loads = [];
        $allPaths = array_unique(array_merge(array_keys($relationColumnFields), array_keys($recordRefColumns)));

        foreach ($allPaths as $path) {
            if (isset($recordRefColumns[$path]) && $recordRefColumns[$path] !== []) {
                $firstColumn = $recordRefColumns[$path][0];
                $provider = app(RecordReferenceRegistry::class)->resolve($firstColumn->getApplicationCode());
                $relation = $this->firstSegmentRelation($model, $path);

                if ($provider && $relation instanceof BelongsTo) {
                    $relatedClass = get_class($relation->getRelated());
                    if (! is_a($relatedClass, $provider->modelClass(), true)) {
                        throw new InvalidModelException("Record reference column [{$firstColumn->getKey()}] points to [{$relatedClass}], expected [{$provider->modelClass()}].");
                    }

                    $wanted = [$relation->getOwnerKeyName()];
                    foreach ($recordRefColumns[$path] as $col) {
                        $wanted = array_merge(
                            $wanted,
                            $provider->identityColumns(),
                            $col->getVariant() === RecordReferenceVariant::Card ? $provider->cardColumns() : []
                        );
                    }
                    $wanted = array_merge($wanted, $relationColumnFields[$path] ?? []);

                    $constrainedRecordReferences[$path] = function ($relationQuery) use ($provider, $wanted): void {
                        $builder = $relationQuery instanceof Relation ? $relationQuery->getQuery() : $relationQuery;
                        $provider->scopeQuery($builder)
                            ->select(array_values(array_unique($wanted)));
                    };

                    continue;
                }
            }

            if (isset($relationColumnFields[$path])) {
                if (! str_contains($path, '.')) {
                    try {
                        $relation = $model->{$path}();
                        $keys = match (true) {
                            $relation instanceof BelongsTo => [$relation->getOwnerKeyName()],
                            $relation instanceof HasMany, $relation instanceof HasOne => [$relation->getRelated()->getKeyName(), Str::afterLast($relation->getForeignKeyName(), '.')],
                            default => [$relation->getRelated()->getKeyName()],
                        };

                        $wanted = array_merge($keys, $relationColumnFields[$path]);
                        $constrainedRecordReferences[$path] = function ($relationQuery) use ($wanted): void {
                            $relationQuery->select(array_values(array_unique($wanted)));
                        };
                    } catch (\Throwable) {
                        // ignore
                    }
                }
            }
        }

        $loads = [];
        foreach ($allPaths as $path) {
            if (isset($constrainedRecordReferences[$path])) {
                $loads[$path] = $constrainedRecordReferences[$path];
            } else {
                $loads[] = $path;
            }
        }

        if ($loads !== []) {
            $query->with($loads);
        }
    }

    protected function applySearch(Builder $query, TableState $state): void
    {
        if ($state->search === '') {
            return;
        }

        $searchableColumns = array_filter($this->definition->authorizedColumns(), fn ($column) => $column->isSearchable());

        if ($searchableColumns === []) {
            return;
        }

        $this->searchDriver->search($query, $state->search, $searchableColumns);
    }

    protected function applyFilters(Builder $query, TableState $state): void
    {
        foreach ($state->filters as $key => $entry) {
            $filter = $this->definition->filter($key);

            if (! $filter) {
                continue;
            }

            $this->applyFilter($query, $filter, $entry['operator'], $entry['value']);
        }
    }

    /**
     * Runs the given closure against $query directly, or via whereHas() when the
     * filter targets a dotted relation path — this guarantees a to-many relation
     * filter can never fan out or duplicate parent rows (whereHas is exists-based).
     */
    protected function scopedWhere(Builder $query, Filter $filter, callable $callback): void
    {
        if ($filter->isRelationFilter()) {
            $relationPath = Str::beforeLast($filter->getKey(), '.');
            $field = Str::afterLast($filter->getKey(), '.');
            $query->whereHas($relationPath, fn (Builder $relationQuery) => $callback($relationQuery, $field));

            return;
        }

        $callback($query, $filter->getKey());
    }

    protected function applyFilter(Builder $query, Filter $filter, string $operator, mixed $value): void
    {
        match (true) {
            $filter instanceof TextFilter => $this->scopedWhere($query, $filter, fn (Builder $q, string $field) => $this->applyTextOperator($q, $field, $operator, $value)),
            $filter instanceof NumberFilter => $this->scopedWhere($query, $filter, fn (Builder $q, string $field) => $this->applyNumberOperator($q, $field, $operator, $value)),
            $filter instanceof DateFilter => $this->scopedWhere($query, $filter, fn (Builder $q, string $field) => $this->applyDateOperator($q, $field, $operator, $value)),
            $filter instanceof BooleanFilter => $this->scopedWhere($query, $filter, fn (Builder $q, string $field) => $q->where($field, (bool) $value)),
            $filter instanceof EnumFilter => $this->applyEnumFilter($query, $filter, $operator, $value),
            $filter instanceof BelongsToFilter => $this->applyBelongsToFilter($query, $filter, $operator, $value),
            default => null,
        };
    }

    protected function applyTextOperator(Builder $q, string $field, string $operator, mixed $value): void
    {
        $escaped = is_string($value) ? addcslashes($value, '\\%_') : $value;

        match ($operator) {
            'contains' => $q->where($field, 'like', "%{$escaped}%"),
            'equals' => $q->where($field, '=', $value),
            'starts_with' => $q->where($field, 'like', "{$escaped}%"),
            'ends_with' => $q->where($field, 'like', "%{$escaped}"),
            'does_not_contain' => $q->where($field, 'not like', "%{$escaped}%"),
            'does_not_equal' => $q->where($field, '!=', $value),
            'is_empty' => $q->where(fn (Builder $inner) => $inner->whereNull($field)->orWhere($field, '')),
            'is_not_empty' => $q->whereNotNull($field)->where($field, '!=', ''),
            default => null,
        };
    }

    protected function applyNumberOperator(Builder $q, string $field, string $operator, mixed $value): void
    {
        match ($operator) {
            'equals' => $q->where($field, '=', $value),
            'does_not_equal' => $q->where($field, '!=', $value),
            'greater_than' => $q->where($field, '>', $value),
            'greater_than_or_equal' => $q->where($field, '>=', $value),
            'less_than' => $q->where($field, '<', $value),
            'less_than_or_equal' => $q->where($field, '<=', $value),
            'between' => $q->whereBetween($field, $value),
            'not_between' => $q->whereNotBetween($field, $value),
            'is_empty' => $q->whereNull($field),
            'is_not_empty' => $q->whereNotNull($field),
            default => null,
        };
    }

    /**
     * $value arrives fully resolved (already converted to the app/database
     * timezone) by TableState::normalizeDateFilterEntry() — no Carbon parsing
     * or boundary math happens here. See that method for why day-boundary
     * computation has to happen in Core, where the filter's timezone is known.
     */
    protected function applyDateOperator(Builder $q, string $field, string $operator, mixed $value): void
    {
        match ($operator) {
            'on', 'between', 'today', 'yesterday', 'this_week', 'this_month' => $q->whereBetween($field, $value),
            'before' => $q->where($field, '<', $value),
            'before_or_on' => $q->where($field, '<=', $value),
            'after' => $q->where($field, '>', $value),
            'after_or_on' => $q->where($field, '>=', $value),
            'not_between' => $q->whereNotBetween($field, $value),
            'is_empty' => $q->whereNull($field),
            'is_not_empty' => $q->whereNotNull($field),
            default => null,
        };
    }

    protected function applyEnumFilter(Builder $query, EnumFilter $filter, string $operator, mixed $value): void
    {
        $this->scopedWhere($query, $filter, function (Builder $q, string $field) use ($operator, $value): void {
            match ($operator) {
                'equals' => $q->where($field, $value),
                'in' => $q->whereIn($field, $value),
                default => null,
            };
        });
    }

    protected function applyBelongsToFilter(Builder $query, BelongsToFilter $filter, string $operator, mixed $value): void
    {
        $relationName = $filter->getKey();

        $query->whereHas($relationName, function (Builder $relationQuery) use ($operator, $value): void {
            $keyName = $relationQuery->getModel()->getKeyName();

            match ($operator) {
                'equals' => $relationQuery->where($keyName, $value),
                'in' => $relationQuery->whereIn($keyName, $value),
                default => null,
            };
        });
    }

    protected function applySort(Builder $query, TableState $state): void
    {
        foreach ($state->sorts as $sort) {
            $column = $this->definition->column($sort['column']);

            if (! $column) {
                continue;
            }

            if ($column instanceof RelationColumn) {
                $this->applyRelationSort($query, $column, $sort['direction']);

                continue;
            }

            $query->orderBy($column->getField(), $sort['direction']);
        }

        // Stable pagination: always tie-break on the primary key.
        $model = $query->getModel();
        $query->orderBy($model->getQualifiedKeyName(), 'asc');
    }

    protected function applyRelationSort(Builder $query, RelationColumn $column, string $direction): void
    {
        $model = $query->getModel();
        $relation = $this->firstSegmentRelation($model, $column->getRelationPath());

        if ($relation instanceof HasMany || $relation instanceof BelongsToMany) {
            if (! $column->getAggregate()) {
                throw HasManySortWithoutAggregateException::forKey($column->getKey());
            }

            // Laravel's withAggregate() alias convention: {relation}_{function}_{column}.
            $alias = Str::snake($column->getRelationPath()).'_'.$column->getAggregate().'_'.$column->getRelationField();
            $query->withAggregate($column->getRelationPath(), $column->getRelationField(), $column->getAggregate());
            $query->orderBy($alias, $direction);

            return;
        }

        if ($relation instanceof BelongsTo) {
            $relatedTable = $relation->getRelated()->getTable();
            $subQuery = $relation->getRelated()->newQuery()
                ->select($column->getRelationField())
                ->whereColumn("{$relatedTable}.{$relation->getOwnerKeyName()}", $model->getTable().'.'.$relation->getForeignKeyName())
                ->limit(1);

            $query->orderBy($subQuery, $direction);

            return;
        }

        // Other relation types (HasOne, etc.) are not supported for sort in this milestone.
    }

    protected function firstSegmentRelation(mixed $model, string $relationPath): mixed
    {
        $relationName = Str::before($relationPath, '.');

        return $model->{$relationName}();
    }
}
