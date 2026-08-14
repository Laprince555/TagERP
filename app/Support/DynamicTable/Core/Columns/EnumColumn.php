<?php

namespace App\Support\DynamicTable\Core\Columns;

use App\Support\DynamicTable\Core\Column;
use App\Support\DynamicTable\Core\Exceptions\InvalidEnumConfigurationException;
use BackedEnum;

class EnumColumn extends Column
{
    /** @var class-string<BackedEnum> */
    protected string $enumClass;

    /**
     * @param  class-string<BackedEnum>  $enumClass
     */
    public function enum(string $enumClass): static
    {
        if (! is_subclass_of($enumClass, BackedEnum::class)) {
            throw InvalidEnumConfigurationException::notBackedEnum($enumClass);
        }

        $this->enumClass = $enumClass;

        return $this;
    }

    public function formatValue(mixed $value, mixed $row): mixed
    {
        if ($value === null) {
            return $this->placeholder ?? $value;
        }

        if ($this->formatUsing) {
            return ($this->formatUsing)($value, $row);
        }

        $case = $value instanceof BackedEnum ? $value : ($this->enumClass)::tryFrom($value);

        return $case?->name ?? $value;
    }
}
