<?php

namespace App\Support\DynamicTable\Core\Filters;

use App\Support\DynamicTable\Core\Filter;
use App\Support\DynamicTable\Core\NumberOperator;

class NumberFilter extends Filter
{
    /** @var NumberOperator[]|null */
    protected ?array $operators = null;

    /**
     * @param  NumberOperator[]  $operators
     */
    public function operators(array $operators): static
    {
        $this->operators = $operators;

        return $this;
    }

    /**
     * @return NumberOperator[]
     */
    public function getOperators(): array
    {
        return $this->operators ?? NumberOperator::cases();
    }
}
