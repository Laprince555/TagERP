<?php

namespace App\Support\DynamicForm\Core;

/**
 * One step of a CascadingRelationField (e.g. Country -> State -> City).
 * Each level after the first is constrained by the previous level's picked
 * row via dependsOn() + foreignKey() (the column on THIS level's model that
 * points at the previous level's id).
 */
class CascadingLevel
{
    protected ?string $displayField = null;

    protected ?string $dependsOn = null;

    protected ?string $foreignKey = null;

    protected int $pageSize = 5;

    protected int $maximumLoadedResults = 50;

    protected function __construct(protected string $key, protected string $model) {}

    /** @param  class-string  $model */
    public static function make(string $key, string $model): static
    {
        return new static($key, $model);
    }

    public function field(string $field): static
    {
        $this->displayField = $field;

        return $this;
    }

    public function getDisplayField(): ?string
    {
        return $this->displayField;
    }

    /** Previous level's key this one is filtered by, e.g. state's ->dependsOn('country'). */
    public function dependsOn(string $levelKey): static
    {
        $this->dependsOn = $levelKey;

        return $this;
    }

    public function getDependsOn(): ?string
    {
        return $this->dependsOn;
    }

    /** Column on THIS level's model pointing at the parent level's id. Defaults to "{dependsOn}_id". */
    public function foreignKey(string $column): static
    {
        $this->foreignKey = $column;

        return $this;
    }

    public function getForeignKey(): string
    {
        return $this->foreignKey ?? $this->dependsOn.'_id';
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

    public function getKey(): string
    {
        return $this->key;
    }

    public function getModel(): string
    {
        return $this->model;
    }
}
