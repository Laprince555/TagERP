<?php

namespace App\Support\DynamicRecordView\Core\Exceptions;

class DuplicateTabKeyException extends DynamicRecordViewException
{
    public static function forKey(string $key): self
    {
        return new self("Duplicate record view tab key [{$key}].");
    }
}
