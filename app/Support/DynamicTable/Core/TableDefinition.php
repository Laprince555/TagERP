<?php

namespace App\Support\DynamicTable\Core;

use App\Support\DynamicTable\Core\Exceptions\DuplicateColumnKeyException;
use App\Support\DynamicTable\Core\Exceptions\DuplicateFilterKeyException;
use App\Support\DynamicTable\Core\Exceptions\MissingTableKeyException;
use App\Support\DynamicTable\Core\Exceptions\UnknownFieldMappingException;
use Closure;

/**
 * Immutable, trusted description of one table instance: its columns, filters,
 * base query and default sort. Rebuilt fresh every request by the Livewire
 * component — never serialized into Livewire's public state.
 */
class TableDefinition
{
    /** @var array<string, Column> keyed by column key */
    public readonly array $columns;

    /** @var array<string, Filter> keyed by filter key */
    public readonly array $filters;

    /**
     * @param  Column[]  $columns
     * @param  Filter[]  $filters
     * @param  Closure  $query  Returns an Illuminate\Database\Eloquent\Builder
     * @param  Sort[]  $defaultSort
     */
    public function __construct(
        public readonly string $tableKey,
        array $columns,
        array $filters,
        public readonly Closure $query,
        public readonly array $defaultSort = [],
    ) {
        if ($this->tableKey === '') {
            throw MissingTableKeyException::make();
        }

        $keyed = [];
        foreach ($columns as $column) {
            if (isset($keyed[$column->getKey()])) {
                throw DuplicateColumnKeyException::forKey($column->getKey());
            }
            // Final, order-independent validation — runs once the whole fluent
            // chain for this column has already completed (see ComputedColumn).
            $column->validate();
            $keyed[$column->getKey()] = $column;
        }
        $this->columns = $keyed;

        $keyedFilters = [];
        foreach ($filters as $filter) {
            if (isset($keyedFilters[$filter->getKey()])) {
                throw DuplicateFilterKeyException::forKey($filter->getKey());
            }
            $keyedFilters[$filter->getKey()] = $filter;
        }
        $this->filters = $keyedFilters;

        foreach ($this->defaultSort as $sort) {
            if (! isset($keyed[$sort->getColumn()])) {
                throw UnknownFieldMappingException::forKey($sort->getColumn());
            }
        }
    }

    /**
     * Returns the column for $key, or null if it doesn't exist OR its visible()
     * condition is currently false. This is the single authorization gate for
     * every caller that resolves a column by key — an unauthorized column must
     * be treated as if it does not exist (select, sort, search, eager load,
     * toggle, reorder, export, preferences, saved views).
     */
    public function column(string $key): ?Column
    {
        $column = $this->columns[$key] ?? null;

        return ($column && $column->isVisible()) ? $column : null;
    }

    /**
     * @return array<string, Column> only columns whose visible() condition is currently true
     */
    public function authorizedColumns(): array
    {
        return array_filter($this->columns, fn (Column $column) => $column->isVisible());
    }

    /**
     * Returns the filter for $key, or null if it doesn't exist OR is currently
     * unauthorized. This is the single authorization gate every caller (query
     * engine, TableState normalization, filter-panel rendering) routes
     * through. Authorization resolution order:
     *
     * 1. If the filter declared its own visible() condition, that alone decides.
     * 2. Otherwise, if a column shares the filter's exact key, the filter
     *    inherits that column's authorization — so a filter targeting a
     *    column that later becomes unauthorized automatically goes with it.
     * 3. Otherwise (a filter-only field with no matching column and no
     *    explicit condition), it defaults to authorized, matching Column's
     *    own default-visible stance.
     */
    public function filter(string $key): ?Filter
    {
        $filter = $this->filters[$key] ?? null;

        if (! $filter) {
            return null;
        }

        if ($filter->hasExplicitVisibility()) {
            return $filter->isVisible() ? $filter : null;
        }

        $matchingColumn = $this->columns[$key] ?? null;

        if ($matchingColumn && ! $matchingColumn->isVisible()) {
            return null;
        }

        return $filter->isVisible() ? $filter : null;
    }

    /**
     * @return array<string, Filter> only filters currently authorized per filter()'s resolution order
     */
    public function authorizedFilters(): array
    {
        return array_filter($this->filters, fn (Filter $filter) => $this->filter($filter->getKey()) !== null);
    }
}
