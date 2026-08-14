<?php

namespace App\Support\DynamicRecordView\Resolution;

use App\Support\DynamicRecordView\Core\Exceptions\UnsupportedUnlinkForNonNullableForeignKeyException;
use App\Support\DynamicRecordView\Core\RelationshipActions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Executes Link/Unlink mutations declared via RelationshipActions. Every
 * entry point re-resolves the parent + content block fresh through
 * EmbeddedTableContext (same trusted path constrain() uses for reads),
 * re-fetches the candidate/related row by id through an authorized query
 * (never trusting client-supplied row data), and re-checks eligibility
 * inside a locking transaction — see link()/unlink() for the exact steps.
 *
 * Never accepts a relation name, model class, or SQL field from the client —
 * only the bounded scalar identifiers (recordViewKey, parent id, section,
 * tab, content key, candidate/related id) any embedded Table already carries.
 */
class RelationshipMutator
{
    public function __construct(
        protected EmbeddedTableContext $embeddedContext,
    ) {}

    /**
     * @param  class-string  $tableClass
     */
    public function link(
        string $tableClass,
        string $recordViewKey,
        int|string $parentId,
        string $section,
        string $tab,
        string $contentKey,
        int|string $candidateId,
    ): void {
        [$parent, $content] = $this->embeddedContext->resolveEmbeddedContent($tableClass, $recordViewKey, $parentId, $section, $tab, $contentKey);

        $relationName = $content->getRelation();
        abort_if($relationName === null, 404);

        $actions = $content->getRelationshipActions();
        $this->safeAbortUnless($actions !== null && $actions->isLinkable());

        DB::transaction(function () use ($parent, $relationName, $candidateId, $actions) {
            // Re-lock and re-check inside the transaction — a concurrent
            // request may have changed either row since the pre-transaction
            // resolution above.
            $lockedParent = $parent->newQuery()->lockForUpdate()->find($parent->getKey());
            $this->safeAbortUnless($lockedParent !== null);

            $relation = $this->embeddedContext->resolveRelation($lockedParent, $relationName);
            $relatedClass = $relation->getRelated()::class;

            $candidate = $relatedClass::query()->lockForUpdate()->find($candidateId);
            $this->safeAbortUnless($candidate !== null);

            $authorize = $actions->getLinkAuthorization();
            $this->safeAbortUnless($authorize === null || $authorize(auth()->user(), $lockedParent, $candidate));

            if ($mutateUsing = $actions->getMutateUsing()) {
                $mutateUsing($lockedParent, $relation, $candidate, 'link');

                return;
            }

            $this->performLink($lockedParent, $relation, $candidate, $actions);
        });
    }

    /**
     * @param  class-string  $tableClass
     */
    public function unlink(
        string $tableClass,
        string $recordViewKey,
        int|string $parentId,
        string $section,
        string $tab,
        string $contentKey,
        int|string $relatedId,
    ): void {
        [$parent, $content] = $this->embeddedContext->resolveEmbeddedContent($tableClass, $recordViewKey, $parentId, $section, $tab, $contentKey);

        $relationName = $content->getRelation();
        abort_if($relationName === null, 404);

        $actions = $content->getRelationshipActions();
        $this->safeAbortUnless($actions !== null && $actions->isUnlinkable());

        try {
            $actions->assertSupportedFor($parent::class, $relationName);
        } catch (UnsupportedUnlinkForNonNullableForeignKeyException $e) {
            $this->safeAbortUnless(false);
        }

        DB::transaction(function () use ($parent, $relationName, $relatedId, $actions) {
            $lockedParent = $parent->newQuery()->lockForUpdate()->find($parent->getKey());
            $this->safeAbortUnless($lockedParent !== null);

            $relation = $this->embeddedContext->resolveRelation($lockedParent, $relationName);
            $relatedClass = $relation->getRelated()::class;

            $related = $relatedClass::query()->lockForUpdate()->find($relatedId);
            $this->safeAbortUnless($related !== null);

            $authorize = $actions->getUnlinkAuthorization();
            $this->safeAbortUnless($authorize === null || $authorize(auth()->user(), $lockedParent, $related));

            if ($mutateUsing = $actions->getMutateUsing()) {
                $mutateUsing($lockedParent, $relation, $related, 'unlink');

                return;
            }

            $this->performUnlink($lockedParent, $relation, $related);
        });
    }

    protected function performLink(Model $parent, Relation $relation, Model $candidate, RelationshipActions $actions): void
    {
        if ($relation instanceof BelongsToMany) {
            // syncWithoutDetaching never duplicates an existing pivot row —
            // an already-linked candidate is a safe, idempotent no-op.
            $relation->syncWithoutDetaching([$candidate->getKey()]);

            return;
        }

        if ($relation instanceof HasMany || $relation instanceof MorphMany) {
            $fk = $relation->getForeignKeyName();
            $currentParentId = $candidate->getAttribute($fk);

            // Idempotent: already linked to this exact parent.
            if ($currentParentId !== null && (string) $currentParentId === (string) $parent->getKey()) {
                return;
            }

            // Never steal a child currently owned by a different parent
            // unless the relation explicitly opted in.
            $this->safeAbortUnless($currentParentId === null || $actions->reassignmentAllowed());

            $candidate->setAttribute($fk, $parent->getKey());
            $candidate->save();

            return;
        }

        abort(422, 'Unable to link.');
    }

    protected function performUnlink(Model $parent, Relation $relation, Model $related): void
    {
        if ($relation instanceof BelongsToMany) {
            $relation->detach($related->getKey());

            return;
        }

        if ($relation instanceof HasMany || $relation instanceof MorphMany) {
            $fk = $relation->getForeignKeyName();
            $related->setAttribute($fk, null);
            $related->save();

            return;
        }

        abort(422, 'Unable to unlink.');
    }

    /**
     * Aborts with a generic, existence-agnostic error — never "record #47
     * doesn't exist" vs. "not authorized", so a client can't probe for valid
     * ids by comparing error messages.
     */
    protected function safeAbortUnless(bool $condition): void
    {
        if (! $condition) {
            throw new HttpException(422, 'Unable to complete this action.');
        }
    }
}
