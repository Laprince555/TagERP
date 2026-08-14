<?php

namespace App\Support\DynamicTable\Core\Exceptions;

class SortableComputedColumnWithoutDataSourceException extends DynamicTableException
{
    public static function forKey(string $key): self
    {
        return new self("ComputedColumn [{$key}] cannot be sortable() unless it declares a real field via ->field() first.");
    }
}
