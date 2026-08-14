<?php

namespace App\Support\DynamicRecordView\Core\Fields;

class NumberViewField extends Field
{
    protected int $decimals = 0;

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

        return number_format((float) $value, $this->decimals);
    }
}
