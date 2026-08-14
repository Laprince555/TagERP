<?php

namespace App\Support\DynamicRecordView\Core\Fields;

class BooleanViewField extends Field
{
    protected string $trueLabel = 'Yes';

    protected string $falseLabel = 'No';

    public function labels(string $true, string $false): static
    {
        $this->trueLabel = $true;
        $this->falseLabel = $false;

        return $this;
    }

    protected function formattedValue(mixed $value, mixed $record): mixed
    {
        if ($value === null) {
            return null;
        }

        return $value ? $this->trueLabel : $this->falseLabel;
    }
}
