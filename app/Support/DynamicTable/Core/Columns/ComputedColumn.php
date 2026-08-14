<?php

namespace App\Support\DynamicTable\Core\Columns;

use App\Support\DynamicTable\Core\Column;
use App\Support\DynamicTable\Core\Exceptions\FilterTargetUnavailableException;
use App\Support\DynamicTable\Core\Exceptions\SortableComputedColumnWithoutDataSourceException;

/**
 * A column whose display value is computed from the row rather than a single
 * underlying field. sortable()/searchable() are only valid once a real
 * field/subquery has been declared via ->field() — validated once, in
 * validate(), regardless of the order the fluent methods were called in
 * (TableDefinition::__construct() calls validate() on every column after
 * the whole fluent chain has run, so "field() after sortable()" and
 * "field() before sortable()" behave identically).
 */
class ComputedColumn extends Column
{
    public function validate(): void
    {
        if ($this->isSortable() && ! $this->hasExplicitField()) {
            throw SortableComputedColumnWithoutDataSourceException::forKey($this->getKey());
        }

        if ($this->isSearchable() && ! $this->hasExplicitField()) {
            throw FilterTargetUnavailableException::forKey($this->getKey());
        }
    }
}
