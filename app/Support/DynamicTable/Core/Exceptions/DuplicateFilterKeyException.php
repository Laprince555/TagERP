<?php

namespace App\Support\DynamicTable\Core\Exceptions;

class DuplicateFilterKeyException extends DynamicTableException
{
    public static function forKey(string $key): self
    {
        return new self("Duplicate filter key [{$key}] registered on the same table definition. Filter keys must be unique.");
    }
}
