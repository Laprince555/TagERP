<?php

namespace App\Support\DynamicTable\Core\Columns;

use App\Support\DynamicTable\Core\Column;
use App\Support\DynamicTable\Core\Exceptions\UnsupportedRelationPathException;
use Illuminate\Support\Str;

/**
 * Dotted key = relation path + field, e.g. RelationColumn::make('country.name').
 */
class RelationColumn extends Column
{
    protected ?string $aggregate = null;

    protected function __construct(string $key)
    {
        if (! str_contains($key, '.')) {
            throw UnsupportedRelationPathException::forKey($key);
        }

        if (Str::beforeLast($key, '.') === '' || Str::afterLast($key, '.') === '') {
            throw UnsupportedRelationPathException::forKey($key);
        }

        parent::__construct($key);
    }

    /**
     * Declares a real aggregate (count/sum/...) that makes a to-many relation sortable.
     */
    public function aggregate(string $function): static
    {
        $this->aggregate = $function;

        return $this;
    }

    public function getAggregate(): ?string
    {
        return $this->aggregate;
    }

    public function getRelationPath(): string
    {
        return Str::beforeLast($this->getField(), '.');
    }

    public function getRelationField(): string
    {
        return Str::afterLast($this->getField(), '.');
    }

    /**
     * The select alias the aggregate lands in. Always passed to
     * withAggregate() explicitly as "{relation} as {alias}" rather than left
     * to Laravel to derive: Laravel strips the dots out of a nested relation
     * path when deriving one, so a path like `country.state` would be read
     * back under a name nobody here computes the same way.
     */
    public function getAggregateAlias(): ?string
    {
        if ($this->aggregate === null) {
            return null;
        }

        return Str::snake(str_replace('.', '_', $this->getRelationPath()))
            .'_'.$this->aggregate.'_'.$this->getRelationField();
    }

    /**
     * Where the rendered value actually lives on the row — the aggregate
     * alias for aggregate columns, the dotted relation path otherwise.
     * Reading an aggregate column through its dotted path would return the
     * whole related collection (and lazy-load it per row), never the number.
     */
    public function getValuePath(): string
    {
        return $this->getAggregateAlias() ?? $this->getField();
    }
}
