<?php

namespace App\Support\DynamicTable\Core\Filters;

use App\Support\DynamicTable\Core\DateOperator;
use App\Support\DynamicTable\Core\Filter;

class DateFilter extends Filter
{
    /** @var DateOperator[]|null */
    protected ?array $operators = null;

    protected bool $withTime = false;

    protected ?string $timezone = null;

    /**
     * @param  DateOperator[]  $operators
     */
    public function operators(array $operators): static
    {
        $this->operators = $operators;

        return $this;
    }

    /**
     * @return DateOperator[]
     */
    public function getOperators(): array
    {
        return $this->operators ?? DateOperator::cases();
    }

    public function withTime(bool $withTime = true): static
    {
        $this->withTime = $withTime;

        return $this;
    }

    public function hasTime(): bool
    {
        return $this->withTime;
    }

    /**
     * The timezone submitted date/datetime values are interpreted in before being
     * converted to the database/app timezone (config('app.timezone')) for querying.
     * Not resolved until getTimezone() is called, so it always reflects the current
     * request/config, never a value baked in at column-definition time.
     */
    public function timezone(string $timezone): static
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getTimezone(): string
    {
        return $this->timezone ?? config('app.timezone', 'UTC');
    }
}
