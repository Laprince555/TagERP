<?php

namespace App\Support\DynamicTable\Core\Exceptions;

class DuplicateColumnKeyException extends DynamicTableException
{
    public static function forKey(string $key): self
    {
        return new self("Duplicate column key [{$key}] registered on the same table definition. Column keys must be unique.");
    }
}
