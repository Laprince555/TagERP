<?php

namespace App\Support\DynamicTable\Core\Exceptions;

class InvalidModelException extends DynamicTableException
{
    public static function forQuery(): self
    {
        return new self('The Dynamic Table query() definition must return an Illuminate\Database\Eloquent\Builder instance.');
    }

    public static function forMissingModelOrQuery(string $tableClass): self
    {
        return new self("[{$tableClass}] must either set a protected \$model = SomeModel::class property or override query().");
    }

    public static function forInvalidModel(string $model): self
    {
        return new self("[{$model}] is not a valid Illuminate\Database\Eloquent\Model subclass.");
    }
}
