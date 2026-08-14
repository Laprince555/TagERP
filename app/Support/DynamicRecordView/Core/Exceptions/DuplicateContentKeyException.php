<?php

namespace App\Support\DynamicRecordView\Core\Exceptions;

class DuplicateContentKeyException extends DynamicRecordViewException
{
    public static function forKey(string $key): self
    {
        return new self("Duplicate record view content key [{$key}].");
    }
}
