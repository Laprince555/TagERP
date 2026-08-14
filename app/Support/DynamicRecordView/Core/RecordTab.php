<?php

namespace App\Support\DynamicRecordView\Core;

use App\Support\DynamicRecordView\Core\Content\Content;
use App\Support\DynamicRecordView\Core\Exceptions\DuplicateContentKeyException;

/**
 * One tab inside a RecordSection. Holds an ordered list of Content blocks.
 */
class RecordTab
{
    /** @var array<string, Content> */
    protected array $contents = [];

    protected ?string $label = null;

    protected bool $default = false;

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

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label ?? str($this->key)->headline()->toString();
    }

    public function default(bool $default = true): static
    {
        $this->default = $default;

        return $this;
    }

    public function isDefault(): bool
    {
        return $this->default;
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

    /**
     * @param  Content[]  $contents
     */
    public function contents(array $contents): static
    {
        foreach ($contents as $content) {
            if (isset($this->contents[$content->getKey()])) {
                throw DuplicateContentKeyException::forKey($content->getKey());
            }

            $this->contents[$content->getKey()] = $content;
        }

        return $this;
    }

    /**
     * @return Content[]
     */
    public function getContents(): array
    {
        return array_values($this->contents);
    }
}
