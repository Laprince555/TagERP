<?php

namespace App\Support\DynamicRecordView\Core\Exceptions;

class DuplicateSectionKeyException extends DynamicRecordViewException
{
    public static function forKey(string $key): self
    {
        return new self("Duplicate record view section key [{$key}].");
    }
}
