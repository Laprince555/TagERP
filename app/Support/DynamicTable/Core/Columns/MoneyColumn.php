<?php

namespace App\Support\DynamicTable\Core\Columns;

class MoneyColumn extends NumberColumn
{
    protected string $currency = 'USD';

    public function currency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function formatValue(mixed $value, mixed $row): mixed
    {
        if ($value === null || $value === '') {
            return $this->placeholder ?? $value;
        }

        if ($this->formatUsing) {
            return ($this->formatUsing)($value, $row);
        }

        return $this->currency.' '.number_format((float) $value, $this->decimals ?: 2);
    }
}
