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
    /**
     * A nested save announces itself separately from a top-level one. Both
     * are the same $formKey when a field points at its own definition (an
     * Account's parent is an Account), and the plain event is what the
     * hosting FormModal closes on and the hosting table refreshes on —
     * creating a parent inline would otherwise tear down the very form the
     * user is still filling in.
     */
    public const NESTED_SAVED_EVENT = 'dynamic-form-nested-saved.';

    #[Locked]
    public string $formKey;

    /** @var array<string, mixed> keyed by each field's key() (not its persisted column). */
    public array $data = [];

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

    /**
     * Which RelationListField's inline create form is expanded, or '' for
     * none. Only one at a time, and never in a form that is itself nested —
     * see $nested.
     */
    public string $openCreateField = '';

    /**
     * True for a Form rendered inside another Form's relation picker. Such a
     * form renders no create buttons of its own, so "add the missing
     * Company" can never open "add the missing Country" three levels deep,
     * each level holding unsaved state the user would lose.
     */
    #[Locked]
    public bool $nested = false;

    /**
     * The record being edited, or null to create one. Locked: the id is fixed
     * by whoever mounted the form, and save() re-resolves it server-side
     * rather than trusting it to still point where the client says.
     */
    #[Locked]
    public int|string|null $recordId = null;

    /**
     * True when $recordId is only a template: the form prefills from it and
     * then forgets it, so saving creates a new record instead of updating the
     * one it was copied from.
     */
    #[Locked]
    public bool $copy = false;

    /**
     * The record a copy was prefilled from. Kept after $recordId is cleared so
     * the definition can carry the parts of it that are not form fields —
     * a Journal's lines — into the new record.
     */
    #[Locked]
    public int|string|null $copySourceId = null;

    public function mount(string $formKey, bool $nested = false, int|string|null $recordId = null, bool $copy = false): void
    {
        $this->formKey = $formKey;
        $this->nested = $nested;
        $this->recordId = $recordId;
        $this->copy = $copy;

        if ($recordId !== null) {
            $this->fillFromRecord();
        } else {
            $this->fillDefaults();
        }

        if ($copy) {
            $this->copySourceId = $recordId;
            $this->recordId = null;
        }
    }

    public function isEditing(): bool
    {
        return $this->recordId !== null;
    }

    /** Seeds $data with each field's declared default() before the user types anything. */
    protected function fillDefaults(): void
    {
        foreach ($this->definition()->fields() as $field) {
            if ($field->getDefault() !== null) {
                $this->data[$field->getKey()] = $field->getDefault();
            }
        }
    }

    /**
     * Loads the edited record's current values into the same $data shape a
     * create form builds up by typing, so every field renders and validates
     * through exactly one path.
     *
     * A RelationListField also needs the chosen record's display label, not
     * just its id, or the picker's trigger button opens showing "Select…"
     * over a value that is already set.
     *
     * ponytail: CascadingRelationField prefills its stored leaf id but not the
     * ancestor labels on the trigger — no form that has one is editable yet.
     * Resolve the chain in fillFromRecord() when the first one is.
     */
    protected function fillFromRecord(): void
    {
        $definition = $this->definition();
        $record = $definition->findForEdit($this->recordId);

        if ($record === null || ! $definition->authorize()) {
            throw new NotFoundHttpException;
        }

        if (! ($this->copy ? $definition->authorizeCopy($record) : $definition->authorizeUpdate($record))) {
            throw new NotFoundHttpException;
        }

        foreach ($definition->fields() as $field) {
            $value = data_get($record, $this->columnFor($field));
            $this->data[$field->getKey()] = $value;

            if (! $field instanceof RelationListField || $value === null) {
                continue;
            }

            $relatedClass = $field->getModel();
            $displayField = $field->getDisplayField();

            if ($relatedClass === null || $displayField === null) {
                continue;
            }

            $related = $relatedClass::query()->find($value);

            if ($related !== null) {
                $this->relationSelected[$field->getKey()] = [
                    'id' => $value,
                    'label' => (string) data_get($related, $displayField),
                ];
            }
        }
    }

    /**
     * Listens for a save from every nested create form this definition
     * declares, so the record the user just created is picked automatically
     * — the whole point of the button.
     *
     * @return array<string, string>
     */
    protected function getListeners(): array
    {
        $listeners = [];

        foreach ($this->definition()->fields() as $field) {
            if ($field instanceof RelationListField && $field->getCreateForm() !== null) {
                $listeners[self::NESTED_SAVED_EVENT.$field->getCreateForm()] = 'selectCreatedRelation';
            }
        }

        return $listeners;
    }

    /** Collapse if this create form is already expanded, otherwise open it. */
    public function toggleCreateForm(string $fieldKey): void
    {
        if ($this->openCreateField === $fieldKey) {
            $this->openCreateField = '';

            return;
        }

        if ($this->createFormKeyFor($fieldKey) === null) {
            return;
        }

        $this->openCreateField = $fieldKey;
    }

    /**
     * The create form key a field may open, or null when the field declares
     * none, this form is itself nested, or the actor is not allowed to
     * create through it. Checked here as well as in the view so a crafted
     * toggleCreateForm() cannot mount a form the button never offered.
     */
    public function createFormKeyFor(string $fieldKey): ?string
    {
        if ($this->nested) {
            return null;
        }

        $field = $this->fieldByKey($fieldKey);

        if (! $field instanceof RelationListField) {
            return null;
        }

        $formKey = $field->getCreateForm();

        if ($formKey === null) {
            return null;
        }

        return app(FormDefinitionRegistry::class)->resolve($formKey)->authorizeOutOfContext() ? $formKey : null;
    }

    /**
     * Picks the just-created record. Its label is read back through the
     * field's own query() scope rather than taken from the event: a record
     * the field is not allowed to offer must not become its value just
     * because the user created it from here.
     */
    public function selectCreatedRelation(int|string $id): void
    {
        // Nothing to fill when no create form is expanded — a stray event
        // must not overwrite a value the user picked normally.
        $fieldKey = $this->openCreateField;

        if ($fieldKey === '') {
            return;
        }

        $field = $this->fieldByKey($fieldKey);

        if (! $field instanceof RelationListField) {
            return;
        }

        $modelClass = $field->getModel();
        $query = $modelClass::query();

        if ($constrain = $field->getQuery()) {
            $query = $constrain($query);
        }

        $record = $query->find($id);

        $this->openCreateField = '';

        if ($record === null) {
            return;
        }

        $this->selectRelation($fieldKey, $record->getKey(), (string) data_get($record, $field->getDisplayField()));
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

        // A nested form was opened from an unrelated page, so page access
        // proves nothing about it — it carries its own gate all the way to
        // the write, not just to whether the button rendered.
        $authorized = $this->nested ? $definition->authorizeOutOfContext() : $definition->authorize();

        // A create — including a Copy, which is a create with prefilled
        // values — additionally needs the Application's create permission.
        // Reaching the form proved read access and nothing more.
        if ($authorized && ($this->copy || ! $this->isEditing())) {
            $authorized = $definition->authorizeCreate();
        }

        if (! $authorized) {
            throw new NotFoundHttpException;
        }

        $fields = $definition->fields();
        $rules = [];
        $attributes = [];

        // Rules are keyed by the wire:model path (data.{fieldKey}), not by the
        // persisted column, so Livewire's own error bag holds them — that is
        // what Flux reads to mark an invalid input. Validating a detached
        // array through Validator::make() left the bag empty and every field
        // rendered as if it were still valid.
        foreach ($fields as $field) {
            $field->validate();
            $path = 'data.'.$field->getKey();
            $rules[$path] = $field->getRules();
            $attributes[$path] = $field->getLabel();
        }

        $this->validate($rules, [], $attributes);

        $payload = [];

        foreach ($fields as $field) {
            $payload[$this->columnFor($field)] = $this->data[$field->getKey()] ?? null;
        }

        if ($this->copy) {
            $source = $definition->findForEdit($this->copySourceId);

            if ($source === null || ! $definition->authorizeCopy($source)) {
                throw new NotFoundHttpException;
            }

            $record = $definition->createCopy($payload, $source);
        } elseif ($this->isEditing()) {
            $existing = $definition->findForEdit($this->recordId);

            // Re-checked on the write, not merely when the button rendered:
            // the record can have been posted, deleted, or moved out of reach
            // since the modal was opened.
            if ($existing === null || ! $definition->authorizeUpdate($existing)) {
                throw new NotFoundHttpException;
            }

            $record = $definition->update($existing, $payload);
        } else {
            $record = $definition->create($payload);
        }

        $this->dispatch(
            ($this->nested ? self::NESTED_SAVED_EVENT : 'dynamic-form-saved.').$this->formKey,
            id: $record->getKey(),
        );

        // A copy lands on the record it just created rather than leaving the
        // user on the one it was copied from — the new document is what they
        // are going to work on next.
        if ($this->copy && ($url = $definition->recordUrl($record)) !== null) {
            $this->redirect($url, navigate: true);

            return;
        }

        // An edit form stays on the record it edits; only a create form is
        // reset so the next, empty one starts clean.
        if ($this->isEditing()) {
            return;
        }

        // Every picker's state resets too, not just $data — a leftover
        // cascadeSelected keeps showing the saved record's Country/State on
        // the trigger button of the next, empty form.
        $this->reset(
            'data',
            'relationSelected',
            'relationSearch',
            'relationResults',
            'relationHasMore',
            'activeRelationField',
            'cascadeSelected',
            'cascadeSearch',
            'cascadeResults',
            'cascadeHasMore',
            'openCascadeField',
            'openCreateField',
        );
    }

    public function render()
    {
        return view('livewire.dynamic-form.form', [
            'definition' => $this->definition(),
        ]);
    }
}
