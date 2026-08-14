<?php

namespace App\Support\DynamicRecordView\Core\Fields;

class EnumViewField extends Field
{
    /** @var array<string, string> */
    protected array $labels = [];

    /**
     * @param  array<string, string>  $labels  raw value => display label
     */
    public function labels(array $labels): static
    {
        $this->labels = $labels;

        return $this;
    }

    protected function formattedValue(mixed $value, mixed $record): mixed
    {
        if ($value === null) {
            return null;
        }

        $raw = $value instanceof \BackedEnum ? $value->value : $value;

        return $this->labels[$raw] ?? ($value instanceof \UnitEnum ? $value->name : $raw);
    }
}
