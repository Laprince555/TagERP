<?php

namespace App\Support\DynamicTable\Core\Columns;

use App\Support\DynamicTable\Core\Column;

class NumberColumn extends Column
{
    protected int $decimals = 0;

    public function decimals(int $decimals): static
    {
        $this->decimals = $decimals;

        return $this;
    }

    public function getDecimals(): int
    {
        return $this->decimals;
    }

    public function formatValue(mixed $value, mixed $row): mixed
    {
        if ($value === null || $value === '') {
            return $this->placeholder ?? $value;
        }

        if ($this->formatUsing) {
            return ($this->formatUsing)($value, $row);
        }

        return number_format((float) $value, $this->decimals);
    }
}
