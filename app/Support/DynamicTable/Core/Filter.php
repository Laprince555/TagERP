<?php

namespace App\Support\DynamicTable\Core;

abstract class Filter
{
    protected ?string $label = null;

    protected mixed $visible = null;

    protected function __construct(protected string $key) {}

    public static function make(string $key): static
    {
        return new static($key);
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Explicitly declares this filter's own authorization, independent of any
     * column that happens to share its key — for a filter-only field with no
     * matching column. When not called, TableDefinition::filter() instead
     * inherits authorization from a same-key column, if one exists.
     */
    public function visible(callable|bool $visible): static
    {
        $this->visible = $visible;

        return $this;
    }

    public function hasExplicitVisibility(): bool
    {
        return $this->visible !== null;
    }

    public function isVisible(mixed $context = null): bool
    {
        if ($this->visible === null) {
            return true;
        }

        return is_callable($this->visible) ? (bool) ($this->visible)($context) : (bool) $this->visible;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLabel(): string
    {
        return $this->label ?? str($this->key)->headline()->toString();
    }

    public function isRelationFilter(): bool
    {
        return str_contains($this->key, '.');
    }
}
