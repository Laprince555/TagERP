<?php

namespace App\Support\DynamicTable\Core\Columns;

use App\Support\DynamicTable\Core\Column;

class BooleanColumn extends Column
{
    protected string $trueLabel = 'Yes';

    protected string $falseLabel = 'No';

    public function labels(string $true, string $false): static
    {
        $this->trueLabel = $true;
        $this->falseLabel = $false;

        return $this;
    }

    public function formatValue(mixed $value, mixed $row): mixed
    {
        if ($this->formatUsing) {
            return ($this->formatUsing)($value, $row);
        }

        return $value ? $this->trueLabel : $this->falseLabel;
    }
}
