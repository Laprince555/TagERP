<?php

namespace App\Livewire\DynamicForm;

use App\Support\DynamicForm\Core\CascadingLevel;
use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Field;
use App\Support\DynamicForm\Core\Fields\CascadingRelationField;
use App\Support\DynamicForm\Core\Fields\RelationListField;
use App\Support\DynamicForm\Core\FormDefinitionRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Generic create form host, driven entirely by a registered DynamicForm
 * definition — never hand-built per Application. One instance renders
 * every Field the definition declares; validate()/save() work purely off
 * that declared shape, never a hardcoded field list.
 *
 * Hosted two ways with zero duplication: full page (App\Livewire\DynamicForm\FormPage)
 * or modal (App\Livewire\DynamicForm\FormModal) — both just mount this same
 * component with the same $formKey.
 */
class Form extends Component
{
    #[Locked]
    public string $formKey;

    /** @var array<string, mixed> keyed by each field's key() (not its persisted column). */
    public array $data = [];

    /** @var array<string, string> validation error messages keyed by field key. */
    public array $errors_ = [];

    // --- Relation list picker state (one nested picker per RelationListField, keyed by field key) ---
    public string $activeRelationField = '';

    public array $relationSearch = [];

    /** @var array<string, array<int, array{id: int|string, label: string}>> */
    public array $relationResults = [];

    public array $relationHasMore = [];

    /** @var array<string, array{id: int|string, label: string}|null> */
    public array $relationSelected = [];

    // --- Cascading picker state, all keyed [fieldKey][levelKey] ---

    /** @var array<string, array<string, array{id: int|string, label: string}>> */
    public array $cascadeSelected = [];

    /** @var array<string, array<string, string>> */
    public array $cascadeSearch = [];

    /** @var array<string, array<string, array<int, array{id: int|string, label: string}>>> */
    public array $cascadeResults = [];

    /** @var array<string, array<string, bool>> */
    public array $cascadeHasMore = [];

    /**
     * Which cascading field is currently expanded, or '' for none.
     *
     * The picker is an inline panel rather than a nested <flux:modal>: this
     * form is itself usually rendered inside FormModal's <dialog>, and a
     * dialog nested in an open dialog is re-created by Livewire's DOM morph
     * on the very request that tries to open it, so the show event lands on
     * a detached element and nothing appears. Server-rendered open state has
     * no such race.
     */
    public string $openCascadeField = '';

    public function mount(string $formKey): void
    {
        $this->formKey = $formKey;
    }

    protected function definition(): DynamicForm
    {
        return app(FormDefinitionRegistry::class)->resolve($this->formKey);
    }

    protected function fieldByKey(string $key): ?Field
    {
        foreach ($this->definition()->fields() as $field) {
            if ($field->getKey() === $key) {
                return $field;
            }
        }

        return null;
    }

    protected function columnFor(Field $field): string
    {
        return method_exists($field, 'getColumn') ? $field->getColumn() : $field->getKey();
    }

    /**
     * Opens the bounded search picker for one RelationListField. Nothing is
     * queried until this is called.
     */
    /** Collapse if this picker is already expanded, otherwise open it. */
    public function toggleRelationPicker(string $fieldKey): void
    {
        if ($this->activeRelationField === $fieldKey) {
            $this->activeRelationField = '';

            return;
        }

        $this->openRelationPicker($fieldKey);
    }

    public function openRelationPicker(string $fieldKey): void
    {
        $this->activeRelationField = $fieldKey;
        $this->relationSearch[$fieldKey] = '';
        $this->relationResults[$fieldKey] = [];
        $this->relationHasMore[$fieldKey] = false;
        $this->loadRelationResults($fieldKey);
        $this->dispatch('relation-list-picker-opened.'.$this->formKey.'.'.$fieldKey);
    }

    public function updatedRelationSearch(mixed $value, string $fieldKey): void
    {
        $this->relationResults[$fieldKey] = [];
        $this->relationHasMore[$fieldKey] = false;
        $this->loadRelationResults($fieldKey);
    }

    public function loadMoreRelation(string $fieldKey): void
    {
        $this->loadRelationResults($fieldKey);
    }

    protected function loadRelationResults(string $fieldKey): void
    {
        $field = $this->fieldByKey($fieldKey);

        if (! $field instanceof RelationListField) {
            return;
        }

        $loaded = $this->relationResults[$fieldKey] ?? [];

        if (count($loaded) >= $field->getMaximumLoadedResults()) {
            $this->relationHasMore[$fieldKey] = false;

            return;
        }

        $modelClass = $field->getModel();
        $keyName = (new $modelClass)->getKeyName();
        $displayField = $field->getDisplayField();
        $searchFields = $field->getSearchFields();

        $query = $modelClass::query()->select(array_values(array_unique([$keyName, $displayField, ...$searchFields])));

        if ($constrain = $field->getQuery()) {
            $query = $constrain($query);
        }

        $term = (string) ($this->relationSearch[$fieldKey] ?? '');

        if ($term !== '' && $searchFields !== []) {
            $escaped = addcslashes($term, '\\%_');
            $query->where(function (Builder $q) use ($searchFields, $escaped): void {
                foreach ($searchFields as $searchField) {
                    $q->orWhere($searchField, 'like', "%{$escaped}%");
                }
            });
        }

        $loadedIds = array_column($loaded, 'id');
        if ($loadedIds !== []) {
            $query->whereNotIn($keyName, $loadedIds);
        }

        $take = min($field->getPageSize(), $field->getMaximumLoadedResults() - count($loaded));
        $page = $query->orderBy($displayField)->orderBy($keyName)->limit($take + 1)->get();

        $this->relationHasMore[$fieldKey] = $page->count() > $take;

        $newResults = $page->take($take)->map(fn (Model $record) => [
            'id' => $record->getKey(),
            'label' => (string) data_get($record, $displayField),
        ])->all();

        $this->relationResults[$fieldKey] = [...$loaded, ...$newResults];
    }

    public function selectRelation(string $fieldKey, int|string $id, string $label): void
    {
        $this->relationSelected[$fieldKey] = ['id' => $id, 'label' => $label];
        $this->data[$fieldKey] = $id;
        $this->activeRelationField = '';
        $this->dispatch('close-relation-list-picker.'.$this->formKey.'.'.$fieldKey);
    }

    /**
     * The label is looked up server-side from the already-loaded candidate
     * list rather than passed in from the view: interpolating a record's
     * name into a wire:click expression breaks on any label containing a
     * quote (700 seeded cities contain an apostrophe — "N'Goussa",
     * "O'Connor"), and would mean trusting client-supplied display text.
     * Only the id crosses the wire, and it must match a candidate this
     * component actually loaded.
     */
    public function chooseRelation(string $fieldKey, int|string $id): void
    {
        $candidate = collect($this->relationResults[$fieldKey] ?? [])
            ->firstWhere('id', $id);

        if ($candidate === null) {
            return;
        }

        $this->selectRelation($fieldKey, $candidate['id'], $candidate['label']);
    }

    // ---------------------------------------------------------------
    // Cascading picker (Country -> State -> City)
    // ---------------------------------------------------------------

    /**
     * Opens the multi-step picker and loads only its FIRST level. Deeper
     * levels stay unqueried until their parent is chosen — the whole point
     * of the cascade against a 150k-row City table.
     */
    public function openCascadePicker(string $fieldKey): void
    {
        $field = $this->fieldByKey($fieldKey);

        if (! $field instanceof CascadingRelationField) {
            return;
        }

        $this->cascadeSelected[$fieldKey] = [];
        $this->cascadeSearch[$fieldKey] = [];
        $this->cascadeResults[$fieldKey] = [];
        $this->cascadeHasMore[$fieldKey] = [];
        $this->data[$fieldKey] = null;

        $first = $field->getLevels()[0];
        $this->cascadeSearch[$fieldKey][$first->getKey()] = '';
        $this->loadCascadeResults($fieldKey, $first->getKey());

        $this->openCascadeField = $fieldKey;
    }

    public function closeCascadePicker(): void
    {
        $this->openCascadeField = '';
    }

    /** Single entry point for the trigger button: collapse if open, otherwise open. */
    public function toggleCascadePicker(string $fieldKey): void
    {
        if ($this->openCascadeField === $fieldKey) {
            $this->closeCascadePicker();

            return;
        }

        $this->openCascadePicker($fieldKey);
    }

    /**
     * Choosing a level always invalidates every level below it — otherwise
     * picking a new Country would silently keep the previous Country's
     * State/City. Only the last level writes the persisted value.
     */
    public function chooseCascade(string $fieldKey, string $levelKey, int|string $id): void
    {
        $field = $this->fieldByKey($fieldKey);

        if (! $field instanceof CascadingRelationField) {
            return;
        }

        $candidate = collect($this->cascadeResults[$fieldKey][$levelKey] ?? [])
            ->firstWhere('id', $id);

        if ($candidate === null) {
            return;
        }

        $this->cascadeSelected[$fieldKey][$levelKey] = $candidate;

        // Drop everything below the level just chosen.
        $seen = false;
        foreach ($field->getLevels() as $level) {
            if ($level->getKey() === $levelKey) {
                $seen = true;

                continue;
            }

            if ($seen) {
                unset(
                    $this->cascadeSelected[$fieldKey][$level->getKey()],
                    $this->cascadeResults[$fieldKey][$level->getKey()],
                    $this->cascadeHasMore[$fieldKey][$level->getKey()],
                    $this->cascadeSearch[$fieldKey][$level->getKey()],
                );
            }
        }

        $last = $field->lastLevel();

        if ($last !== null && $last->getKey() === $levelKey) {
            $this->data[$fieldKey] = $candidate['id'];
            $this->openCascadeField = '';

            return;
        }

        // Not the last level — the value isn't complete yet.
        $this->data[$fieldKey] = null;

        $next = $this->nextCascadeLevel($field, $levelKey);

        if ($next !== null) {
            $this->cascadeSearch[$fieldKey][$next->getKey()] = '';
            $this->loadCascadeResults($fieldKey, $next->getKey());
        }
    }

    /**
     * "Change" on an already-chosen level: drop it and everything below,
     * then reload its candidates so the user can re-pick without starting
     * the whole cascade over.
     */
    public function reopenCascadeLevel(string $fieldKey, string $levelKey): void
    {
        $field = $this->fieldByKey($fieldKey);

        if (! $field instanceof CascadingRelationField) {
            return;
        }

        $clearing = false;

        foreach ($field->getLevels() as $level) {
            if ($level->getKey() === $levelKey) {
                $clearing = true;
            }

            if (! $clearing) {
                continue;
            }

            unset(
                $this->cascadeSelected[$fieldKey][$level->getKey()],
                $this->cascadeResults[$fieldKey][$level->getKey()],
                $this->cascadeHasMore[$fieldKey][$level->getKey()],
            );
            $this->cascadeSearch[$fieldKey][$level->getKey()] = '';
        }

        $this->data[$fieldKey] = null;
        $this->loadCascadeResults($fieldKey, $levelKey);
    }

    public function updatedCascadeSearch(mixed $value, string $path): void
    {
        // $path arrives as "{fieldKey}.{levelKey}".
        [$fieldKey, $levelKey] = array_pad(explode('.', $path, 2), 2, '');

        if ($fieldKey === '' || $levelKey === '') {
            return;
        }

        $this->cascadeResults[$fieldKey][$levelKey] = [];
        $this->cascadeHasMore[$fieldKey][$levelKey] = false;
        $this->loadCascadeResults($fieldKey, $levelKey);
    }

    public function loadMoreCascade(string $fieldKey, string $levelKey): void
    {
        $this->loadCascadeResults($fieldKey, $levelKey);
    }

    protected function nextCascadeLevel(CascadingRelationField $field, string $levelKey): ?CascadingLevel
    {
        $seen = false;

        foreach ($field->getLevels() as $level) {
            if ($seen) {
                return $level;
            }

            if ($level->getKey() === $levelKey) {
                $seen = true;
            }
        }

        return null;
    }

    /**
     * Loads one page of one level, constrained by the parent level's chosen
     * id. A level whose parent has not been chosen yet loads nothing at all
     * — that is what "locked until the previous one is picked" means on the
     * server, independent of whatever the view renders.
     */
    protected function loadCascadeResults(string $fieldKey, string $levelKey): void
    {
        $field = $this->fieldByKey($fieldKey);

        if (! $field instanceof CascadingRelationField) {
            return;
        }

        $level = $field->levelByKey($levelKey);

        if ($level === null) {
            return;
        }

        $parent = $field->parentLevel($levelKey);
        $parentSelection = $parent === null
            ? null
            : ($this->cascadeSelected[$fieldKey][$parent->getKey()] ?? null);

        if ($parent !== null && $parentSelection === null) {
            $this->cascadeResults[$fieldKey][$levelKey] = [];
            $this->cascadeHasMore[$fieldKey][$levelKey] = false;

            return;
        }

        $loaded = $this->cascadeResults[$fieldKey][$levelKey] ?? [];

        if (count($loaded) >= $level->getMaximumLoadedResults()) {
            $this->cascadeHasMore[$fieldKey][$levelKey] = false;

            return;
        }

        $modelClass = $level->getModel();
        $keyName = (new $modelClass)->getKeyName();
        $displayField = $level->getDisplayField();

        $query = $modelClass::query()->select(array_values(array_unique([$keyName, $displayField])));

        if ($parent !== null) {
            $query->where($level->getForeignKey(), $parentSelection['id']);
        }

        $term = (string) ($this->cascadeSearch[$fieldKey][$levelKey] ?? '');

        if ($term !== '') {
            $escaped = addcslashes($term, '\\%_');
            $query->where($displayField, 'like', "%{$escaped}%");
        }

        $loadedIds = array_column($loaded, 'id');
        if ($loadedIds !== []) {
            $query->whereNotIn($keyName, $loadedIds);
        }

        $take = min($level->getPageSize(), $level->getMaximumLoadedResults() - count($loaded));
        $page = $query->orderBy($displayField)->orderBy($keyName)->limit($take + 1)->get();

        $this->cascadeHasMore[$fieldKey][$levelKey] = $page->count() > $take;

        $newResults = $page->take($take)->map(fn (Model $record) => [
            'id' => $record->getKey(),
            'label' => (string) data_get($record, $displayField),
        ])->all();

        $this->cascadeResults[$fieldKey][$levelKey] = [...$loaded, ...$newResults];
    }

    /**
     * Whether $levelKey is currently selectable: the first level always is,
     * any other only once its parent has been chosen.
     */
    public function cascadeLevelUnlocked(string $fieldKey, string $levelKey): bool
    {
        $field = $this->fieldByKey($fieldKey);

        if (! $field instanceof CascadingRelationField) {
            return false;
        }

        $parent = $field->parentLevel($levelKey);

        return $parent === null
            || isset($this->cascadeSelected[$fieldKey][$parent->getKey()]);
    }

    public function save(): void
    {
        $definition = $this->definition();

        if (! $definition->authorize()) {
            throw new NotFoundHttpException;
        }

        $fields = $definition->fields();
        $rules = [];
        $payload = [];

        foreach ($fields as $field) {
            $field->validate();
            $column = $this->columnFor($field);
            $rules[$column] = $field->getRules();
            $payload[$column] = $this->data[$field->getKey()] ?? null;
        }

        $validator = Validator::make($payload, $rules, [], array_combine(
            array_keys($rules),
            array_map(fn (Field $f) => $f->getLabel(), $fields),
        ));

        if ($validator->fails()) {
            $this->errors_ = $validator->errors()->toArray();

            return;
        }

        $record = $definition->create($validator->validated());

        $this->dispatch('dynamic-form-saved.'.$this->formKey, id: $record->getKey());
        $this->reset('data', 'relationSelected', 'errors_');
    }

    public function render()
    {
        return view('livewire.dynamic-form.form', [
            'definition' => $this->definition(),
        ]);
    }
}
