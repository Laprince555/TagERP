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

    /**
     * The Application this record belongs to, used to render the same
     * application header every index page shows — without it a record page
     * is the only screen in the app that drops that context.
     *
     * Derived from the model's own APPLICATION_CODE so most definitions need
     * nothing; override where the model has no such constant (App\Models\Role,
     * the World reference models), or return null for a view that is not an
     * Application at all (SubModuleRecordView).
     */
    public function applicationCode(): ?string
    {
        $constant = $this->model().'::APPLICATION_CODE';

        return defined($constant) ? constant($constant) : null;
    }

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

    /**
     * Buttons on this record's header bar. Empty by default — a record view
     * that declares none renders no bar at all, so nothing changes for the
     * views that were written before actions existed.
     *
     * @return RecordAction[]
     */
    public function actions(): array
    {
        return [];
    }

    /**
     * The actions the current actor may both use and see, in declared order.
     * Permission first, then the definition's own business rule, so a button
     * the actor could never press is never rendered.
     *
     * This is the only list the Livewire host is allowed to act on: an action
     * key arriving from the client is looked up here, not in actions(), so a
     * forged key for a button that was filtered out finds nothing.
     *
     * @return RecordAction[]
     */
    public function authorizedActions(mixed $record): array
    {
        return array_values(array_filter(
            $this->actions(),
            fn (RecordAction $action): bool => $this->actionPermitted($action) && $action->isVisible($record),
        ));
    }

    public function findAuthorizedAction(string $key, mixed $record): ?RecordAction
    {
        foreach ($this->authorizedActions($record) as $action) {
            if ($action->getKey() === $key) {
                return $action;
            }
        }

        return null;
    }

    /**
     * Fails closed: a view with no applicationCode() has no permission
     * namespace to check against, so it gets no action buttons rather than
     * ungated ones.
     */
    protected function actionPermitted(RecordAction $action): bool
    {
        $code = $this->applicationCode();

        if ($code === null || ! auth()->check()) {
            return false;
        }

        try {
            return (bool) auth()->user()?->can($code.'.'.$action->getPermissionAction());
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Default handler for a `delete` action. Models that refuse deletion in
     * some states (a posted Journal) throw from their own booted() hook, and
     * the host turns that into an error banner rather than a 500.
     *
     * Returns like every action handler: a string is a URL to redirect to
     * afterwards, anything else keeps the actor on the page. Overriding this
     * to return the index route is the usual choice, since the record the
     * page was showing no longer exists.
     */
    public function delete(Model $record): mixed
    {
        $record->delete();

        return null;
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
