<?php

namespace App\Support\DynamicRecordView\Core;

use App\Livewire\DynamicTable\Table;
use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * Definition of one Sub Application shown in the Other Data section
 * (key, applicationKey, label, embedded table, relation, authorization).
 */
class SubApplication
{
    protected ?string $label = null;

    protected ?string $applicationKey = null;

    /** @var class-string<Table>|null */
    protected ?string $table = null;

    /** @var (Closure(mixed): Builder)|null */
    protected ?Closure $forRelation = null;

    /** Preferred over forRelation() — see TableContent::relation(). */
    protected ?string $relation = null;

    /** @var (bool|callable(mixed): bool) */
    protected mixed $authorization = true;

    protected ?RelationshipActions $relationshipActions = null;

    protected function __construct(protected string $key) {}

    public static function make(string $key): static
    {
        return new static($key);
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function applicationKey(string $applicationKey): static
    {
        $this->applicationKey = $applicationKey;

        return $this;
    }

    public function getApplicationKey(): ?string
    {
        return $this->applicationKey;
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
     * @param  class-string<Table>  $table
     */
    public function table(string $table): static
    {
        $this->table = $table;

        return $this;
    }

    public function getTable(): ?string
    {
        return $this->table;
    }

    /**
     * @param  Closure(mixed $record): Builder  $callback
     */
    public function forRelation(Closure $callback): static
    {
        $this->forRelation = $callback;

        return $this;
    }

    public function getForRelation(): ?Closure
    {
        return $this->forRelation;
    }

    public function relation(string $relation): static
    {
        $this->relation = $relation;

        return $this;
    }

    public function getRelation(): ?string
    {
        return $this->relation;
    }

    /**
     * @param  bool|callable(mixed $record): bool  $condition
     */
    public function authorization(bool|callable $condition): static
    {
        $this->authorization = $condition;

        return $this;
    }

    public function isAuthorized(mixed $record = null): bool
    {
        return is_callable($this->authorization) ? (bool) ($this->authorization)($record) : (bool) $this->authorization;
    }

    public function relationshipActions(RelationshipActions $actions): static
    {
        $this->relationshipActions = $actions;

        return $this;
    }

    public function getRelationshipActions(): ?RelationshipActions
    {
        return $this->relationshipActions;
    }
}
