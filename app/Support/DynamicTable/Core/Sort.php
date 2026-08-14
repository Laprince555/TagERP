<?php

namespace App\Support\DynamicTable\Core;

class Sort
{
    protected string $direction = 'asc';

    protected function __construct(protected string $column) {}

    public static function make(string $column): static
    {
        return new static($column);
    }

    public function ascending(): static
    {
        $this->direction = 'asc';

        return $this;
    }

    public function descending(): static
    {
        $this->direction = 'desc';

        return $this;
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }

    /**
     * @return array{column: string, direction: string}
     */
    public function toArray(): array
    {
        return ['column' => $this->column, 'direction' => $this->direction];
    }
}
