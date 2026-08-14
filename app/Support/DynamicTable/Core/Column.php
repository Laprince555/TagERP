<?php

namespace App\Support\DynamicTable\Core;

/**
 * Framework-facing column contract. MUST NOT import any Modules\* class or
 * concrete application Eloquent model.
 */
abstract class Column
{
    protected ?string $label = null;

    protected bool $sortable = false;

    protected bool|array $searchable = false;

    protected bool $hiddenByDefault = false;

    protected bool $toggleable = true;

    protected mixed $visible = true;

    /** @var (callable(mixed, mixed): mixed)|null */
    protected $formatUsing = null;

    protected ?string $placeholder = null;

    /** @var (callable(mixed): ?string)|null */
    protected $link = null;

    protected ?string $align = null;

    protected ?string $width = null;

    protected bool $exportable = true;

    protected ?string $field = null;

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

    public function sortable(bool $sortable = true): static
    {
        $this->sortable = $sortable;

        return $this;
    }

    public function searchable(bool|array $searchable = true): static
    {
        $this->searchable = $searchable;

        return $this;
    }

    public function hiddenByDefault(bool $hidden = true): static
    {
        $this->hiddenByDefault = $hidden;

        return $this;
    }

    public function toggleable(bool $toggleable = true): static
    {
        $this->toggleable = $toggleable;

        return $this;
    }

    public function visible(callable|bool $visible): static
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * @param  callable(mixed $value, mixed $row): mixed  $callback
     */
    public function formatUsing(callable $callback): static
    {
        $this->formatUsing = $callback;

        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    /**
     * @param  callable(mixed $row): ?string  $callback
     */
    public function link(callable $callback): static
    {
        $this->link = $callback;

        return $this;
    }

    public function align(string $align): static
    {
        $this->align = $align;

        return $this;
    }

    public function width(string $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function exportable(bool $exportable = true): static
    {
        // NOTE: config flag only in this milestone. No exporter is implemented.
        $this->exportable = $exportable;

        return $this;
    }

    public function field(string $field): static
    {
        $this->field = $field;

        return $this;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLabel(): string
    {
        return $this->label ?? str($this->key)->headline()->toString();
    }

    public function getField(): string
    {
        return $this->field ?? $this->key;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function isSearchable(): bool
    {
        return (bool) $this->searchable;
    }

    /**
     * @return string[] additional fields to search besides getField(), when searchable() was given an array.
     */
    public function getSearchableFields(): array
    {
        return is_array($this->searchable) ? $this->searchable : [];
    }

    public function isHiddenByDefault(): bool
    {
        return $this->hiddenByDefault;
    }

    public function isToggleable(): bool
    {
        return $this->toggleable;
    }

    public function isVisible(mixed $context = null): bool
    {
        return is_callable($this->visible) ? (bool) ($this->visible)($context) : (bool) $this->visible;
    }

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    public function getAlign(): ?string
    {
        return $this->align;
    }

    public function getWidth(): ?string
    {
        return $this->width;
    }

    public function isExportable(): bool
    {
        return $this->exportable;
    }

    public function hasExplicitField(): bool
    {
        return $this->field !== null;
    }

    /**
     * Final, order-independent configuration validation, called once by
     * TableDefinition::__construct() after every column's fluent chain has
     * already run. Override to reject an invalid final combination of
     * fluent calls (see ComputedColumn) — no-op by default.
     */
    public function validate(): void {}

    /**
     * Resolves the link callback and validates the scheme server-side — only
     * http/https (or scheme-relative/root-relative) URLs are returned. A
     * javascript:, data:, or other dangerous scheme is rejected outright
     * rather than passed through to the view.
     */
    public function getLink(mixed $row): ?string
    {
        $url = $this->link ? ($this->link)($row) : null;

        if ($url === null || $url === '') {
            return null;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        if ($scheme !== null && ! in_array(strtolower($scheme), ['http', 'https'], true)) {
            return null;
        }

        return $url;
    }

    public function formatValue(mixed $value, mixed $row): mixed
    {
        if ($value === null || $value === '') {
            return $this->placeholder ?? $value;
        }

        return $this->formatUsing ? ($this->formatUsing)($value, $row) : $value;
    }
}
