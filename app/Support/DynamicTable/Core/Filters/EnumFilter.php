<?php

namespace App\Support\DynamicTable\Core\Filters;

use App\Support\DynamicTable\Core\Exceptions\InvalidEnumConfigurationException;
use App\Support\DynamicTable\Core\Filter;
use BackedEnum;

class EnumFilter extends Filter
{
    /** @var class-string<BackedEnum> */
    protected string $enumClass;

    protected bool $multiple = false;

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

    /**
     * @return class-string<BackedEnum>
     */
    public function getEnumClass(): string
    {
        return $this->enumClass;
    }

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;

        return $this;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }
}
