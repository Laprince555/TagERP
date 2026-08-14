<?php

namespace App\Support\DynamicRecordView\Core\Exceptions;

class UnknownFieldException extends DynamicRecordViewException
{
    public static function forKey(string $key): self
    {
        return new self("Unknown field [{$key}] referenced in record view configuration.");
    }
}
