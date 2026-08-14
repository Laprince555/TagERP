<?php

namespace App\Support\DynamicRecordView\Core\Exceptions;

class DuplicateFieldKeyException extends DynamicRecordViewException
{
    public static function forKey(string $key): self
    {
        return new self("Duplicate record view field key [{$key}].");
    }
}
