<?php

namespace App\Support\DynamicTable\Core\Exceptions;

class MissingTableKeyException extends DynamicTableException
{
    public static function make(): self
    {
        return new self('A Dynamic Table definition requires a non-empty tableKey. This key namespaces query-string state so multiple table instances do not collide.');
    }
}
