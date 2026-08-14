<?php

namespace App\Support\DynamicRecordView\Core\Exceptions;

/**
 * Thrown at definition time when ->unlink() is enabled on a HasMany/
 * MorphMany relation whose foreign key column is NOT NULL — setting the FK
 * to null to "unlink" would either violate the DB constraint or silently do
 * nothing, so this is rejected up front instead of failing at mutation time.
 */
class UnsupportedUnlinkForNonNullableForeignKeyException extends DynamicRecordViewException
{
    public static function forRelation(string $relation, string $parentModelClass, string $foreignKey): self
    {
        return new self(
            "Relation [{$relation}] on [{$parentModelClass}] cannot support unlink(): its foreign key [{$foreignKey}] is NOT NULL, so it can never be set to null. Remove ->unlink() for this relation, or make the column nullable."
        );
    }
}
