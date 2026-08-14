<?php

namespace App\Support\DynamicTable\Core\Exceptions;

class ModelNotSearchableException extends DynamicTableException
{
    public static function forModel(string $model): self
    {
        return new self("[{$model}] must use the Laravel\Scout\Searchable trait to use ScoutSearchDriver.");
    }
}
