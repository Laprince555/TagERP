<?php

namespace App\Support\DynamicTable\Core\Exceptions;

class HasManySortWithoutAggregateException extends DynamicTableException
{
    public static function forKey(string $key): self
    {
        return new self("RelationColumn [{$key}] targets a HasMany/BelongsToMany relation and cannot be sorted without an explicit ->aggregate() column.");
    }
}
