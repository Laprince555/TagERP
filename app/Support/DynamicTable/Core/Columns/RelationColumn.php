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
}
