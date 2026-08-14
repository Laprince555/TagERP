<?php

namespace App\Support\DynamicTable\Core\Exceptions;

class UnknownFieldMappingException extends DynamicTableException
{
    public static function forKey(string $key): self
    {
        return new self("No column or filter is registered for key [{$key}].");
    }
}
