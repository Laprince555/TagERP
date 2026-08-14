<?php

namespace App\Support\DynamicRecordView\Core\Exceptions;

class MissingViewKeyException extends DynamicRecordViewException
{
    public static function make(): self
    {
        return new self('DynamicRecordView subclasses must set a non-empty $viewKey.');
    }
}
