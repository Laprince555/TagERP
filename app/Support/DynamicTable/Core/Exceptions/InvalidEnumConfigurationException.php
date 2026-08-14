<?php

namespace App\Support\DynamicTable\Core\Exceptions;

class InvalidEnumConfigurationException extends DynamicTableException
{
    public static function notBackedEnum(string $class): self
    {
        return new self("EnumColumn/EnumFilter requires a backed enum class, [{$class}] given.");
    }
}
