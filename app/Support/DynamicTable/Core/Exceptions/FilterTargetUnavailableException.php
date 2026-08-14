<?php

namespace App\Support\DynamicTable\Core\Exceptions;

class FilterTargetUnavailableException extends DynamicTableException
{
    public static function forKey(string $key): self
    {
        return new self("ComputedColumn [{$key}] cannot be searchable() unless it declares a real field via ->field() first.");
    }
}
