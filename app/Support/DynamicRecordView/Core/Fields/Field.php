<?php

namespace App\Support\DynamicRecordView\Core\Fields;

/**
 * Framework-facing read-only field contract. MUST NOT import any Modules\*
 * class or concrete application Eloquent model. Values are resolved from a
 * record (array or object) and rendered escaped by default — no field type
 * emits raw HTML.
 */
abstract class Field
{
    protected ?string $label = null;

    protected mixed $visible = true;

    protected ?string $placeholder = null;

    /** @var (callable(mixed, mixed): mixed)|null */
    protected $formatUsing = null;

    protected bool $copyable = false;

    /** @var (callable(mixed, mixed): ?string)|string|null */
    protected $badge = null;

    protected ?string $icon = null;

    /** @var (callable(mixed, mixed): ?string)|string|null */
    protected $color = null;

    protected int $columnSpan = 1;

    protected ?string $tooltip = null;

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

    /**
     * @param  bool|callable(mixed $record): bool  $condition
     */
    public function visible(bool|callable $condition = true): static
    {
        $this->visible = $condition;

        return $this;
    }

    public function hidden(bool|callable $condition = true): static
    {
        $this->visible = is_callable($condition) ? fn ($record) => ! $condition($record) : ! $condition;

        return $this;
    }

    public function isVisible(mixed $record = null): bool
    {
        return is_callable($this->visible) ? (bool) ($this->visible)($record) : (bool) $this->visible;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function getPlaceholder(): string
    {
        return $this->placeholder ?? '—';
    }

    /**
     * @param  callable(mixed $value, mixed $record): mixed  $callback
     */
    public function formatUsing(callable $callback): static
    {
        $this->formatUsing = $callback;

        return $this;
    }

    public function copyable(bool $copyable = true): static
    {
        $this->copyable = $copyable;

        return $this;
    }

    public function isCopyable(): bool
    {
        return $this->copyable;
    }

    /**
     * @param  (callable(mixed $value, mixed $record): ?string)|string|bool  $badge
     */
    public function badge(callable|string|bool $badge = true): static
    {
        $this->badge = $badge;

        return $this;
    }

    public function getBadge(mixed $value, mixed $record): ?string
    {
        if ($this->badge === null || $this->badge === false) {
            return null;
        }

        if (is_callable($this->badge)) {
            return ($this->badge)($value, $record);
        }

        // badge() called with no args (bare `true`) renders the field's own value as the badge text.
        return $this->badge === true ? (string) $value : (string) $this->badge;
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    /**
     * @param  (callable(mixed $value, mixed $record): ?string)|string  $color
     */
    public function color(callable|string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getColor(mixed $value, mixed $record): ?string
    {
        if ($this->color === null) {
            return null;
        }

        return is_callable($this->color) ? ($this->color)($value, $record) : (string) $this->color;
    }

    public function columnSpan(int $span): static
    {
        $this->columnSpan = max(1, $span);

        return $this;
    }

    public function getColumnSpan(): int
    {
        return $this->columnSpan;
    }

    public function tooltip(string $tooltip): static
    {
        $this->tooltip = $tooltip;

        return $this;
    }

    public function getTooltip(): ?string
    {
        return $this->tooltip;
    }

    /**
     * Resolve the raw value for this field off a record (Eloquent model, array,
     * or anything array/object-accessible). Subclasses may override to reach
     * into dotted relation paths (see RelationViewField, ComputedViewField).
     */
    public function resolveValue(mixed $record): mixed
    {
        return data_get($record, $this->key);
    }

    /**
     * Final display value: resolves the raw value then applies formatUsing()
     * if set, falling back to the raw value untouched. Field subclasses that
     * need type-specific default formatting override formattedValue() instead
     * of duplicating this dispatch.
     */
    public function display(mixed $record): mixed
    {
        $value = $this->resolveValue($record);

        if ($this->formatUsing !== null) {
            return ($this->formatUsing)($value, $record);
        }

        return $this->formattedValue($value, $record);
    }

    /**
     * Type-specific default formatting, applied only when no formatUsing()
     * override is set. Base implementation is a passthrough.
     */
    protected function formattedValue(mixed $value, mixed $record): mixed
    {
        return $value;
    }
}
