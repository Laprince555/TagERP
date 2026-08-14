<?php

namespace App\Support\DynamicTable\Core\Exceptions;

class UnsupportedRelationPathException extends DynamicTableException
{
    public static function forKey(string $key): self
    {
        return new self("RelationColumn/BelongsToFilter key [{$key}] must be a dotted relation path, e.g. 'country.name'.");
    }
}
