<?php

namespace App\Support\DynamicRecordView\Resolution;

use App\Support\DynamicRecordView\Core\Content\TableContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Exceptions\TableModelMismatchException;
use App\Support\DynamicRecordView\Core\Exceptions\UnknownRelationException;
use App\Support\DynamicRecordView\Core\Exceptions\UnknownTableException;
use App\Support\DynamicRecordView\Core\Exceptions\UnsupportedRelationTypeException;
use App\Support\DynamicRecordView\Core\RecordViewRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Resolves the trusted server-side embedding context for one Dynamic Table
 * instance rendered inside a Dynamic Record View, and layers the declared
 * relation's constraint on top of the table's own already-authorized base
 * query. Never replaces that base query — only adds to it (whereIn on the
 * related model's key, scoped to the relation's own query).
 *
 * The browser only ever supplies bounded scalar identifiers (recordViewKey,
 * parent id, section, tab, content key) via Table's #[Locked] embed* props.
 * Every relation name, model class, and authorization decision here is
 * re-resolved fresh from the trusted PHP DynamicRecordView definition on
 * every call — nothing is cached across requests.
 */
class EmbeddedTableContext
{
    public function __construct(
        protected RecordViewRegistry $registry,
        protected RecordResolver $resolver,
    ) {}

    /**
     * @param  class-string  $tableClass  The requesting Table subclass — must exactly match the class declared by the resolved content block.
     */
    public function constrain(
        Builder $baseQuery,
        string $tableClass,
        string $recordViewKey,
        int|string $parentId,
        string $section,
        string $tab,
        string $contentKey,
    ): Builder {
        [$parent, $content] = $this->resolveEmbeddedContent($tableClass, $recordViewKey, $parentId, $section, $tab, $contentKey);

        $relationName = $content->getRelation();

        // No inspectable relation declared (legacy forRelation() closure) —
        // nothing for this adapter to layer on; the table's own base query
        // stands unconstrained. Modules should migrate to relation().
        if ($relationName === null) {
            return $baseQuery;
        }

        return $this->applyRelationConstraint($baseQuery, $parent, $relationName);
    }

    /**
     * Shared fresh-resolution entry point: re-resolves the parent record
     * through its authorized query() and the requesting content block
     * through the trusted server-side definition, from nothing but bounded
     * scalar identifiers. Used by both constrain() (read path) and
     * RelationshipMutator (Link/Unlink mutation path) so the two never
     * diverge on how a parent/content block is authorized.
     *
     * @param  class-string  $tableClass
     * @return array{0: Model, 1: TableContent}
     */
    public function resolveEmbeddedContent(
        string $tableClass,
        string $recordViewKey,
        int|string $parentId,
        string $section,
        string $tab,
        string $contentKey,
    ): array {
        $viewClass = $this->registry->resolve($recordViewKey);
        $definition = app($viewClass);

        $parent = $this->resolver->resolveFresh($definition, $parentId);

        $content = $this->resolveAuthorizedTableContent($definition, $parent, $section, $tab, $contentKey);

        if ($content->getTable() !== $tableClass) {
            throw UnknownTableException::forClass($tableClass);
        }

        return [$parent, $content];
    }

    /**
     * Resolves and validates the named relation on $parent — same existence/
     * type/model checks constrain() applies, reused by RelationshipMutator so
     * a Link/Unlink request can never target an unsupported or spoofed
     * relation either.
     *
     * @param  class-string|null  $expectedRelatedClass
     */
    public function resolveRelation(Model $parent, string $relationName, ?string $expectedRelatedClass = null): Relation
    {
        if (! method_exists($parent, $relationName)) {
            throw UnknownRelationException::forRelation($relationName, $parent::class);
        }

        $relation = $parent->{$relationName}();

        if (! $relation instanceof Relation) {
            throw UnknownRelationException::forRelation($relationName, $parent::class);
        }

        if (! $this->isSupportedRelationInstance($relation)) {
            throw UnsupportedRelationTypeException::forRelation($relationName, $relation::class);
        }

        if ($expectedRelatedClass !== null && $relation->getRelated()::class !== $expectedRelatedClass) {
            throw TableModelMismatchException::forRelation($relationName, $expectedRelatedClass, $relation->getRelated()::class);
        }

        return $relation;
    }

    protected function resolveAuthorizedTableContent(
        DynamicRecordView $definition,
        Model $parent,
        string $section,
        string $tab,
        string $contentKey,
    ): TableContent {
        $recordSection = match ($section) {
            'primary' => $definition->primarySection(),
            'other-data' => $definition->otherDataSection(),
            default => abort(404),
        };

        $authorizedTab = collect($recordSection->authorizedTabs($parent))
            ->first(fn ($candidate) => $candidate->getKey() === $tab);

        abort_if($authorizedTab === null, 404);

        $content = collect($authorizedTab->getContents())
            ->first(fn ($candidate) => $candidate->getKey() === $contentKey);

        abort_if(! $content instanceof TableContent, 404);
        abort_if(! $content->isVisible($parent), 404);

        return $content;
    }

    protected function applyRelationConstraint(Builder $baseQuery, Model $parent, string $relationName): Builder
    {
        $baseModel = $baseQuery->getModel();

        $relation = $this->resolveRelation($parent, $relationName, $baseModel::class);
        $related = $relation->getRelated();
        $relatedKeyName = $related->getKeyName();

        return $baseQuery->whereIn(
            $relatedKeyName,
            $relation->getQuery()->select($related->getQualifiedKeyName()),
        );
    }

    protected function isSupportedRelationInstance(Relation $relation): bool
    {
        return $relation instanceof HasMany || $relation instanceof BelongsToMany || $relation instanceof MorphMany;
    }
}
