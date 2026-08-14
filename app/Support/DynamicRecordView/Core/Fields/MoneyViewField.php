<?php

namespace App\Support\DynamicRecordView\Core\Fields;

class MoneyViewField extends Field
{
    protected string $currency = 'USD';

    protected int $decimals = 2;

    public function currency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function decimals(int $decimals): static
    {
        $this->decimals = $decimals;

        return $this;
    }

    protected function formattedValue(mixed $value, mixed $record): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->currency.' '.number_format((float) $value, $this->decimals);
    }
}
