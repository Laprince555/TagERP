<?php

namespace App\Support\DynamicForm\Core\Fields;

use App\Support\DynamicForm\Core\Field;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

/**
 * A bounded, searchable picker for a belongsTo value — never a plain
 * <select> loading every row. Renders the first pageSize() candidates,
 * narrows by search, and offers Load More up to maximumLoadedResults().
 * Same shape as App\Support\DynamicRecordView\Core\RelationPicker, but
 * standalone (no parent record/relation to constrain by — this fills a
 * not-yet-created record's foreign key).
 *
 * addRelationList('city')->model(City::class)->field('name')->label('City')
 * saves the picked row's key into the "city_id" column (key + "_id"),
 * unless overridden via column().
 */
class RelationListField extends Field
{
    /** @var class-string|null */
    protected ?string $model = null;

    protected ?string $displayField = null;

    /** @var string[] */
    protected array $searchFields = [];

    protected ?string $column = null;

    protected int $pageSize = 5;

    protected int $maximumLoadedResults = 50;

    /** @var (Closure(Builder): Builder)|null */
    protected ?Closure $query = null;

    /** @param  class-string  $model */
    public function model(string $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    /** The column used for both display and search, unless searchable() overrides search fields. */
    public function field(string $field): static
    {
        $this->displayField = $field;

        if ($this->searchFields === []) {
            $this->searchFields = [$field];
        }

        return $this;
    }

    public function getDisplayField(): ?string
    {
        return $this->displayField;
    }

    /** @param  string[]  $fields */
    public function searchable(array $fields): static
    {
        $this->searchFields = $fields;

        return $this;
    }

    /** @return string[] */
    public function getSearchFields(): array
    {
        return $this->searchFields;
    }

    /** Destination column on the form's model. Defaults to "{key}_id". */
    public function column(string $column): static
    {
        $this->column = $column;

        return $this;
    }

    public function getColumn(): string
    {
        return $this->column ?? $this->key.'_id';
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

    /** @param  Closure(Builder $query): Builder  $callback */
    public function query(Closure $callback): static
    {
        $this->query = $callback;

        return $this;
    }

    public function getQuery(): ?Closure
    {
        return $this->query;
    }

    public function component(): string
    {
        return 'dynamic-form.fields.relation-list';
    }

    /**
     * Always constrains the picked value to a row that actually exists, so
     * a forged id reaching save() fails validation instead of exploding on
     * the foreign-key constraint.
     */
    public function getRules(): array
    {
        $model = new ($this->model);
        $connection = $model->getConnectionName();

        // Qualify with the connection only when the model declares one —
        // the World package models do (WorldConnection), most app models
        // don't, and an empty prefix would produce a broken "exists:.table,id".
        $table = $connection === null
            ? $model->getTable()
            : $connection.'.'.$model->getTable();

        return [
            ...parent::getRules(),
            'exists:'.$table.','.$model->getKeyName(),
        ];
    }

    public function validate(): void
    {
        if ($this->model === null) {
            throw new InvalidArgumentException("Relation list field [{$this->key}] must declare model().");
        }

        if ($this->displayField === null) {
            throw new InvalidArgumentException("Relation list field [{$this->key}] must declare field().");
        }
    }
}
