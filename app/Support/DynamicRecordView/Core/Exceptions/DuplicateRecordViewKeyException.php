<?php

namespace App\Support\DynamicRecordView\Core\Exceptions;

class DuplicateRecordViewKeyException extends DynamicRecordViewException
{
    public static function forKey(string $key): self
    {
        return new self("A Dynamic Record View is already registered for key [{$key}].");
    }
}
