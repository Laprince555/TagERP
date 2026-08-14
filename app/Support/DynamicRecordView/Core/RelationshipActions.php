<?php

namespace App\Support\DynamicRecordView\Core;

use App\Support\DynamicRecordView\Core\Exceptions\UnsupportedUnlinkForNonNullableForeignKeyException;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Schema;

/**
 * Configures Link/Unlink mutation support for one embedded relation
 * (TableContent::relationshipActions() / SubApplication::relationshipActions()).
 * Disabled by default — both actions must be explicitly enabled. See
 * App\Support\DynamicRecordView\Resolution\RelationshipMutator for the
 * trusted server-side execution of these actions and
 * docs/dynamic-record-view/relationship-actions.md for the full behavior.
 */
class RelationshipActions
{
    protected bool $linkable = false;

    protected bool $unlinkable = false;

    protected ?RelationPicker $picker = null;

    /** @var (callable(mixed, mixed, mixed): bool)|null */
    protected mixed $linkAuthorization = null;

    /** @var (callable(mixed, mixed, mixed): bool)|null */
    protected mixed $unlinkAuthorization = null;

    protected string $unlinkConfirmationText = 'Remove this relationship? The record itself will not be deleted.';

    /**
     * Explicit opt-in allowing Link to move a HasMany/MorphMany child away
     * from a different parent it's currently attached to (only relevant when
     * the relation's foreign key is NOT NULL, so "unassigned" is never a
     * reachable state — see docs/dynamic-record-view/relationship-actions.md
     * for why this exists and is off by default everywhere else). Never
     * implicitly enabled — a relation without this flag can only link a
     * currently-unassigned (null FK) candidate.
     */
    protected bool $reassignmentAllowed = false;

    /**
     * PLANNED — no reassignment policy object (audit trail, confirmation
     * copy, notification hooks) is implemented in this pass. Calling this
     * always throws so a caller can't silently assume behavior that doesn't
     * exist yet. Use allowReassignment() for the minimal opt-in that is
     * actually wired.
     */
    public function reassignPolicy(mixed $policy): never
    {
        throw new \LogicException(
            'RelationshipActions::reassignPolicy() is not implemented in this pass. Use allowReassignment() for the minimal supported opt-in.'
        );
    }

    /** @var (callable(mixed, mixed, mixed, string): mixed)|null */
    protected mixed $mutateUsing = null;

    public static function make(): static
    {
        return new static;
    }

    public function linkExisting(RelationPicker $picker): static
    {
        $this->linkable = true;
        $this->picker = $picker;

        return $this;
    }

    public function isLinkable(): bool
    {
        return $this->linkable;
    }

    public function getPicker(): ?RelationPicker
    {
        return $this->picker;
    }

    /**
     * @param  callable(mixed $user, mixed $parent, mixed $candidate): bool  $callback
     */
    public function linkAuthorization(callable $callback): static
    {
        $this->linkAuthorization = $callback;

        return $this;
    }

    public function getLinkAuthorization(): ?\Closure
    {
        return $this->linkAuthorization === null ? null : \Closure::fromCallable($this->linkAuthorization);
    }

    public function unlink(): static
    {
        $this->unlinkable = true;

        return $this;
    }

    public function isUnlinkable(): bool
    {
        return $this->unlinkable;
    }

    /**
     * @param  callable(mixed $user, mixed $parent, mixed $related): bool  $callback
     */
    public function unlinkAuthorization(callable $callback): static
    {
        $this->unlinkAuthorization = $callback;

        return $this;
    }

    public function getUnlinkAuthorization(): ?\Closure
    {
        return $this->unlinkAuthorization === null ? null : \Closure::fromCallable($this->unlinkAuthorization);
    }

    public function unlinkConfirmationText(string $text): static
    {
        $this->unlinkConfirmationText = $text;

        return $this;
    }

    public function getUnlinkConfirmationText(): string
    {
        return $this->unlinkConfirmationText;
    }

    public function allowReassignment(): static
    {
        $this->reassignmentAllowed = true;

        return $this;
    }

    public function reassignmentAllowed(): bool
    {
        return $this->reassignmentAllowed;
    }

    /**
     * Exceptional escape hatch: when set, RelationshipMutator delegates the
     * entire mutation to this callback (still inside its transaction, still
     * after authorization) instead of the built-in HasMany/BelongsToMany
     * strategy. No use case in this codebase yet — stub extension point.
     *
     * @param  callable(mixed $parent, mixed $relation, mixed $subject, string $action): mixed  $callback
     */
    public function mutateUsing(callable $callback): static
    {
        $this->mutateUsing = $callback;

        return $this;
    }

    public function getMutateUsing(): ?\Closure
    {
        return $this->mutateUsing === null ? null : \Closure::fromCallable($this->mutateUsing);
    }

    /** @var array<string, bool> memoized nullable-FK schema lookups, process-lifetime only */
    protected static array $nullableForeignKeyCache = [];

    /**
     * Config-time guard: called wherever a relation + these actions are both
     * known (DynamicRecordView::otherDataSection() for SubApplications) so a
     * HasMany/MorphMany relation whose foreign key is NOT NULL fails loudly
     * as soon as ->unlink() is enabled on it, instead of silently no-op'ing
     * (or worse, throwing a DB integrity error) the first time a user clicks
     * Unlink. A no-op stub here would be worse than not implementing it.
     *
     * @param  class-string  $parentModelClass
     */
    public function assertSupportedFor(string $parentModelClass, string $relationName): void
    {
        if (! $this->unlinkable) {
            return;
        }

        $parent = new $parentModelClass;

        if (! method_exists($parent, $relationName)) {
            return; // reported as UnknownRelationException at actual resolution time
        }

        $relation = $parent->{$relationName}();

        if ($relation instanceof BelongsToMany) {
            return; // pivot detach never touches either model's row — always safe
        }

        if (! ($relation instanceof HasMany || $relation instanceof MorphMany)) {
            return; // unsupported relation types are reported by EmbeddedTableContext
        }

        $cacheKey = $parentModelClass.'::'.$relationName;

        if (! isset(self::$nullableForeignKeyCache[$cacheKey])) {
            $table = $relation->getRelated()->getTable();
            $fk = $relation->getForeignKeyName();

            $column = collect(Schema::getColumns($table))->firstWhere('name', $fk);
            self::$nullableForeignKeyCache[$cacheKey] = $column === null ? true : (bool) $column['nullable'];
        }

        if (! self::$nullableForeignKeyCache[$cacheKey]) {
            throw UnsupportedUnlinkForNonNullableForeignKeyException::forRelation($relationName, $parentModelClass, $relation->getForeignKeyName());
        }
    }
}
