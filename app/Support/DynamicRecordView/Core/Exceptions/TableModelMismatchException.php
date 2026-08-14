<?php

namespace App\Support\DynamicRecordView\Core\Exceptions;

class TableModelMismatchException extends DynamicRecordViewException
{
    public static function forRelation(string $relation, string $expected, string $actual): self
    {
        return new self("Embedded table for relation [{$relation}] queries [{$actual}], expected [{$expected}].");
    }
}
