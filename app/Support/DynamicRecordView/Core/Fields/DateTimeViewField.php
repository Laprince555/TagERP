<?php

namespace App\Support\DynamicRecordView\Core\Fields;

use Carbon\Carbon;

class DateTimeViewField extends Field
{
    protected string $format = 'Y-m-d H:i';

    public function format(string $format): static
    {
        $this->format = $format;

        return $this;
    }

    protected function formattedValue(mixed $value, mixed $record): mixed
    {
        if (blank($value)) {
            return null;
        }

        return Carbon::parse($value)->format($this->format);
    }
}
