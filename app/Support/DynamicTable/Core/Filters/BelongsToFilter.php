<?php

namespace App\Support\DynamicTable\Core\Filters;

use App\Support\DynamicTable\Core\Filter;
use Illuminate\Database\Eloquent\Builder;

class BelongsToFilter extends Filter
{
    protected bool $multiple = false;

    protected bool $async = false;

    /** @var (callable(mixed): string)|null */
    protected $displayUsing = null;

    /** @var string[] */
    protected array $searchFields = [];

    /** @var (callable(Builder): Builder)|null */
    protected $optionsQuery = null;

    /** @var (callable(mixed): bool)|null */
    protected $authorizeOption = null;

    public function relation(string $relationName): static
    {
        $this->key = $relationName;

        return $this;
    }

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;

        return $this;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    public function async(bool $async = true): static
    {
        $this->async = $async;

        return $this;
    }

    public function isAsync(): bool
    {
        return $this->async;
    }

    /**
     * @param  callable(mixed $option): string  $callback
     */
    public function displayUsing(callable $callback): static
    {
        $this->displayUsing = $callback;

        return $this;
    }

    public function getDisplayCallback(): ?callable
    {
        return $this->displayUsing;
    }

    /**
     * @param  string[]  $fields
     */
    public function searchUsing(array $fields): static
    {
        $this->searchFields = $fields;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getSearchFields(): array
    {
        return $this->searchFields;
    }

    /**
     * Additional server-defined constraint applied to every option/validation
     * query (search, previously-selected label resolution, selection
     * validation) — e.g. tenant scoping beyond what the relation/global scopes
     * already enforce. Applied on top of the relation's own query, never
     * replacing it.
     *
     * @param  callable(Builder): Builder  $callback
     */
    public function optionsQuery(callable $callback): static
    {
        $this->optionsQuery = $callback;

        return $this;
    }

    public function getOptionsQueryCallback(): ?callable
    {
        return $this->optionsQuery;
    }

    /**
     * Extra per-option authorization check that can't be expressed as a SQL
     * constraint (e.g. a policy call). Evaluated in PHP after the bounded
     * fetch — an option failing this check is dropped from results and can
     * never be accepted as a selected value either.
     *
     * @param  callable(mixed $option): bool  $callback
     */
    public function authorizeOption(callable $callback): static
    {
        $this->authorizeOption = $callback;

        return $this;
    }

    public function getAuthorizeOptionCallback(): ?callable
    {
        return $this->authorizeOption;
    }
}
