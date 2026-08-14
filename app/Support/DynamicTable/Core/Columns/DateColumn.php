<?php

namespace App\Support\DynamicTable\Core\Columns;

use App\Support\DynamicTable\Core\Column;
use Carbon\CarbonInterface;

class DateColumn extends Column
{
    protected string $format = 'Y-m-d';

    public function format(string $format): static
    {
        $this->format = $format;

        return $this;
    }

    public function formatValue(mixed $value, mixed $row): mixed
    {
        if ($value === null || $value === '') {
            return $this->placeholder ?? $value;
        }

        if ($this->formatUsing) {
            return ($this->formatUsing)($value, $row);
        }

        return $value instanceof CarbonInterface ? $value->format($this->format) : $value;
    }
}
