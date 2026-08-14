<?php

namespace App\Support\DynamicRecordView\Core\Exceptions;

class UnknownTableException extends DynamicRecordViewException
{
    public static function forClass(string $class): self
    {
        return new self("Table class [{$class}] does not exist or is not a Dynamic Table.");
    }
}
