<?php

namespace App\Support\DynamicRecordView\Core\Exceptions;

class UnsupportedRelationTypeException extends DynamicRecordViewException
{
    public static function forRelation(string $relation, string $relationClass): self
    {
        return new self("Relation [{$relation}] is a [{$relationClass}], which is not a supported embeddable relation type.");
    }
}
