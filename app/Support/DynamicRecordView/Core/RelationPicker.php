<?php

namespace App\Support\DynamicRecordView\Core;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Configures the searchable candidate picker used by
 * RelationshipActions::linkExisting() to choose a record to link. Rendered
 * by App\Livewire\DynamicRecordView\RelationPickerModal — see
 * docs/dynamic-record-view/relation-picker.md.
 */
class RelationPicker
{
    /** @var (Closure(Builder): Builder)|null additional server-defined constraint, applied on top of the relation's own related-model query */
    protected ?Closure $query = null;

    /** @var string[] */
    protected array $searchable = [];

    /** @var (Closure(mixed): string)|string|null */
    protected mixed $displayUsing = null;

    protected int $pageSize = 5;

    protected int $maximumLoadedResults = 50;

    public const MAX_SEARCH_LENGTH = 100;

    public static function make(): static
    {
        return new static;
    }

    /**
     * @param  Closure(Builder $query): Builder  $callback
     */
    public function query(Closure $callback): static
    {
        $this->query = $callback;

        return $this;
    }

    public function getQuery(): ?Closure
    {
        return $this->query;
    }

    /**
     * @param  string[]  $fields
     */
    public function searchable(array $fields): static
    {
        $this->searchable = $fields;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getSearchable(): array
    {
        return $this->searchable;
    }

    /**
     * @param  (Closure(mixed $record): string)|string  $field  A field name (dot notation supported) or a callback given the candidate record.
     */
    public function displayUsing(Closure|string $field): static
    {
        $this->displayUsing = $field;

        return $this;
    }

    public function display(Model $record): string
    {
        if ($this->displayUsing instanceof Closure) {
            return (string) ($this->displayUsing)($record);
        }

        $field = $this->displayUsing ?? $record->getKeyName();

        return (string) data_get($record, $field);
    }

    public function pageSize(int $size): static
    {
        $this->pageSize = max(1, $size);

        return $this;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function maximumLoadedResults(int $max): static
    {
        $this->maximumLoadedResults = max($this->pageSize, $max);

        return $this;
    }

    public function getMaximumLoadedResults(): int
    {
        return $this->maximumLoadedResults;
    }
}
