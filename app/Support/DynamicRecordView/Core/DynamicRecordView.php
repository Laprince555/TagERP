<?php

namespace App\Support\DynamicRecordView\Core;

use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\Content\TableContent;
use App\Support\DynamicRecordView\Core\Exceptions\MissingViewKeyException;
use App\Support\DynamicRecordView\Core\Fields\RecordReferenceViewField;
use App\Support\DynamicRecordView\Core\Fields\RelationViewField;
use App\Support\DynamicTable\Core\Exceptions\InvalidModelException;
use App\Support\RecordReference\RecordReferenceRegistry;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Base class for one Dynamic Record View definition. Framework-agnostic —
 * MUST NOT import Modules\* classes, Livewire, or Blade. A concrete subclass
 * describes one record type: how to authorize/query it, its header, and its
 * two top-level sections (Primary = tabs(), Other Data = subApplications()).
 */
abstract class DynamicRecordView
{
    /**
     * Namespaces per-instance temporary state (active tabs, embedded table
     * state) so multiple record views on one page, or repeat visits to
     * different records of the same view, never collide.
     */
    protected string $viewKey = '';

    /**
     * @var class-string<Model>
     */
    abstract public function model(): string;

    /**
     * The authorized base query the record must be resolved through — never
     * bare Model::findOrFail(). See RecordResolver.
     */
    abstract public function query(): Builder;

    abstract public function title(mixed $record): string;

    public function subtitle(mixed $record): ?string
    {
        return null;
    }

    /**
     * @return RecordTab[]
     */
    public function tabs(): array
    {
        return [];
    }

    /**
     * @return SubApplication[]
     */
    public function subApplications(): array
    {
        return [];
    }

    public function getViewKey(): string
    {
        if ($this->viewKey === '') {
            throw MissingViewKeyException::make();
        }

        return $this->viewKey;
    }

    public function primarySection(): RecordSection
    {
        return RecordSection::make('primary')->label('Basic Information')->tabs($this->tabs());
    }

    /**
     * Builds one tab per Sub Application, each embedding that application's
     * table via a TableContent block. This is what "Other Data lists Sub
     * Applications as tabs" means concretely — see docs/dynamic-record-view/relations.md.
     */
    public function otherDataSection(): RecordSection
    {
        $tabs = [];

        foreach ($this->subApplications() as $subApplication) {
            $tableContent = TableContent::make($subApplication->getKey().'-table')
                ->table($subApplication->getTable());

            if ($subApplication->getRelation() !== null) {
                $tableContent->relation($subApplication->getRelation());
            } elseif ($subApplication->getForRelation() !== null) {
                $tableContent->forRelation($subApplication->getForRelation());
            }

            if ($subApplication->getRelationshipActions() !== null) {
                $tableContent->relationshipActions($subApplication->getRelationshipActions());

                if ($subApplication->getRelation() !== null) {
                    $subApplication->getRelationshipActions()->assertSupportedFor($this->model(), $subApplication->getRelation());
                }
            }

            $tabs[] = RecordTab::make($subApplication->getKey())
                ->label($subApplication->getLabel())
                ->default($subApplication->isDefault())
                ->visible(fn (mixed $record) => $subApplication->isAuthorized($record))
                ->contents([$tableContent]);
        }

        return RecordSection::make('other-data')->label('Other Data')->tabs($tabs);
    }

    /**
     * Relation paths this view's primary tabs need eager-loaded before the
     * record is fetched, derived from every RelationViewField's dotted key
     * (e.g. `module.name` -> `module`). Walking tabs() here — rather than
     * otherData, whose relations are loaded per-embedded-table separately —
     * avoids N+1 lazy loads when Blade renders a RelationViewField. See
     * RecordResolver::resolve() and docs/dynamic-record-view/performance.md.
     *
     * @return array<int, string>
     */
    public function requiredEagerLoads(): array
    {
        $relations = [];
        $constrained = [];

        foreach ($this->tabs() as $tab) {
            foreach ($tab->getContents() as $content) {
                if (! $content instanceof FieldsContent) {
                    continue;
                }

                foreach ($content->getFields() as $field) {
                    if ($field instanceof RelationViewField && str_contains($field->getKey(), '.')) {
                        $relations[] = str($field->getKey())->beforeLast('.')->toString();
                    }

                    if ($field instanceof RecordReferenceViewField) {
                        $modelInstance = new ($this->model());
                        $field->validate($modelInstance);

                        $relationPath = $field->getRelationPath();
                        if ($relationPath) {
                            $provider = app(RecordReferenceRegistry::class)->resolve($field->getApplicationCode());
                            if ($provider) {
                                $relation = $modelInstance->{$relationPath}();
                                $ownerKey = $relation->getOwnerKeyName();

                                $wanted = array_merge(
                                    [$ownerKey],
                                    $provider->identityColumns(),
                                    $field->getVariant() === RecordReferenceVariant::Card ? $provider->cardColumns() : [],
                                );

                                if (! isset($constrained[$relationPath])) {
                                    $constrained[$relationPath] = [
                                        'provider' => $provider,
                                        'columns' => [],
                                    ];
                                }
                                $constrained[$relationPath]['columns'] = array_merge(
                                    $constrained[$relationPath]['columns'],
                                    $wanted
                                );
                            }
                        } else {
                            $provider = app(RecordReferenceRegistry::class)->resolve($field->getApplicationCode());
                            if ($provider) {
                                $modelClass = get_class($modelInstance);
                                if (! is_a($modelClass, $provider->modelClass(), true)) {
                                    throw new InvalidModelException("Record reference field [{$field->getKey()}] points to [{$modelClass}], expected [{$provider->modelClass()}].");
                                }
                            }
                        }
                    }
                }
            }
        }

        $loads = [];
        foreach (array_unique($relations) as $relation) {
            if (! isset($constrained[$relation])) {
                $loads[] = $relation;
            }
        }
        foreach ($constrained as $path => $info) {
            $provider = $info['provider'];
            $cols = array_values(array_unique($info['columns']));
            $loads[$path] = function ($q) use ($provider, $cols) {
                $builder = $q instanceof Relation ? $q->getQuery() : $q;
                $provider->scopeQuery($builder)->select($cols);
            };
        }

        return $loads;
    }
}
