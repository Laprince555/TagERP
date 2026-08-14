<?php

namespace App\Support\DynamicRecordView\Core\Content;

/**
 * Framework-facing content-block contract. MUST NOT import Modules\* classes,
 * application models, or Blade. There is deliberately no raw-HTML content
 * type — every block is one of the typed subclasses below.
 */
abstract class Content
{
    protected mixed $visible = true;

    protected function __construct(protected string $key) {}

    public static function make(string $key): static
    {
        return new static($key);
    }

    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param  bool|callable(mixed $record): bool  $condition
     */
    public function visible(bool|callable $condition = true): static
    {
        $this->visible = $condition;

        return $this;
    }

    public function isVisible(mixed $record = null): bool
    {
        return is_callable($this->visible) ? (bool) ($this->visible)($record) : (bool) $this->visible;
    }
}
