<?php

namespace App\Support\DynamicRecordView\Core\Content;

use App\Livewire\DynamicTable\Table;
use App\Support\DynamicRecordView\Core\RelationshipActions;
use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * Embeds an existing Dynamic Table (an App\Livewire\DynamicTable\Table
 * subclass) scoped to the current record via an immutable relation
 * constraint. See App\Support\DynamicRecordView\Resolution\EmbeddedTable for
 * how the constraint is applied and kept unescapable.
 */
class TableContent extends Content
{
    /** @var class-string<Table>|null */
    protected ?string $table = null;

    /**
     * @var (Closure(mixed): Builder)|null
     *
     * Given the resolved parent record, returns the constrained base query
     * (e.g. fn ($subModule) => $subModule->applications()).
     */
    protected ?Closure $forRelation = null;

    protected ?string $heading = null;

    protected ?RelationshipActions $relationshipActions = null;

    /**
     * Trusted, inspectable relation name on the parent model (e.g.
     * 'applications'). Preferred over forRelation() — a plain string can be
     * introspected by EmbeddedTableContext (and later the Link/Unlink
     * mutation engine) to find the relation's name and type; a closure
     * cannot.
     */
    protected ?string $relation = null;

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
     * @deprecated Read-only display only. Prefer relation() — a closure
     * cannot be introspected to determine the relation name/type, which the
     * embedded-table adapter (and the later Link/Unlink mutation engine)
     * needs. Kept only for backward compatibility with content blocks that
     * don't need mutation support.
     *
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

    public function heading(string $heading): static
    {
        $this->heading = $heading;

        return $this;
    }

    public function getHeading(): ?string
    {
        return $this->heading;
    }

    /**
     * Enables Link/Unlink mutation UI + server-side mutation for this
     * embedded relation. Only meaningful together with relation() — a
     * forRelation()-only block has nothing inspectable to mutate.
     */
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
