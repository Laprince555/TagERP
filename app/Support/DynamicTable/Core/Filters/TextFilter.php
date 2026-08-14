<?php

namespace App\Support\DynamicTable\Core\Filters;

use App\Support\DynamicTable\Core\Filter;
use App\Support\DynamicTable\Core\TextOperator;

class TextFilter extends Filter
{
    /** @var TextOperator[]|null */
    protected ?array $operators = null;

    /**
     * @param  TextOperator[]  $operators
     */
    public function operators(array $operators): static
    {
        $this->operators = $operators;

        return $this;
    }

    /**
     * @return TextOperator[]
     */
    public function getOperators(): array
    {
        return $this->operators ?? TextOperator::cases();
    }
}
