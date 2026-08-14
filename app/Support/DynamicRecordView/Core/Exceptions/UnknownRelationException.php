<?php

namespace App\Support\DynamicRecordView\Core\Exceptions;

class UnknownRelationException extends DynamicRecordViewException
{
    public static function forRelation(string $relation, string $model): self
    {
        return new self("Unknown relation [{$relation}] on model [{$model}].");
    }
}
