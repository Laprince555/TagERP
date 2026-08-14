<?php

namespace App\Support\DynamicRecordView\Core\Exceptions;

class UnknownRecordViewKeyException extends DynamicRecordViewException
{
    public static function forKey(string $key): self
    {
        return new self("No Dynamic Record View is registered for key [{$key}].");
    }
}
