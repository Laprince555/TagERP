<?php

namespace App\Support\DynamicForm\Core;

use App\Services\NavigationTreeService;
use App\Support\RecordReference\RecordReferenceAccess;
use App\Support\RecordReference\RecordReferenceRegistry;
use Illuminate\Database\Eloquent\Model;

/**
 * Base class for one Dynamic Form definition. Framework-agnostic — MUST NOT
 * import Modules\* classes, Livewire, or Blade. A concrete subclass describes
 * one record type: its model, its fields, and what happens after a successful
 * save.
 *
 * The same definition serves both directions. A form opened with no record id
 * creates (create()); one opened with an id edits that record (update()),
 * which is what a Dynamic Record View's Edit action does. Reaching the form is
 * gated by authorize(); editing one particular record is gated separately by
 * authorizeUpdate(), because "may open the journal form" and "may edit this
 * posted journal" are different questions.
 */
abstract class DynamicForm
{
    protected string $formKey = '';

    /** Set once by FormDefinitionRegistry::resolve() right after instantiation. */
    public function setFormKey(string $formKey): void
    {
        $this->formKey = $formKey;
    }

    /** @var class-string<Model> */
    abstract public function model(): string;

    /** @return Field[] */
    abstract public function fields(): array;

    public function title(): string
    {
        return str($this->getFormKey())->afterLast('.')->headline()->toString();
    }

    public function getFormKey(): string
    {
        if ($this->formKey === '') {
            throw new \RuntimeException(static::class.' must declare a non-empty $formKey.');
        }

        return $this->formKey;
    }

    /**
     * The Application this form creates records for. Declaring it is what
     * makes authorize() a real permission check instead of an open door —
     * see below. Derived from the model's own APPLICATION_CODE so a form
     * cannot be registered without a gate by simply forgetting to add one;
     * override only for a model that has no such constant (App\Models\User,
     * App\Models\Role).
     */
    public function applicationCode(): ?string
    {
        $constant = $this->model().'::APPLICATION_CODE';

        return defined($constant) ? constant($constant) : null;
    }

    /**
     * Extra, form-level authorization beyond the caller's own Application
     * gate (e.g. a business rule). True by default — a form reached from its
     * own Application's page is already gated by that page.
     *
     * NOT the gate for a form opened from somewhere else entirely: a
     * RelationListField's inline create button exposes a form key from an
     * unrelated page, and is gated separately by
     * App\Livewire\DynamicForm\Form::createFormKeyFor().
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Whether the actor may create records through this form at all — the
     * Application's own `create` permission, re-asked on the write rather
     * than inferred from the page the form was opened on. Being able to read
     * a list is not being able to add to it, and this is also the only gate
     * the importer has, since it runs the definition with no page around it.
     *
     * Fails closed when the form declares no applicationCode(): a form with
     * no permission namespace has nothing to check, and "nothing to check"
     * must not read as "allowed".
     */
    public function authorizeCreate(): bool
    {
        $code = $this->applicationCode();

        if ($code === null || ! auth()->check()) {
            return false;
        }

        // Both answers come from caches already warm on any authenticated
        // request — the navigation tree's forever-cache and Spatie's
        // per-request permission set — so this costs no query.
        return app(RecordReferenceAccess::class)->applicationAccessible(
            app(NavigationTreeService::class)->getApplicationByCode($code),
        ) && (bool) auth()->user()?->can($code.'.create');
    }

    /**
     * Whether the current actor may reach this form from outside its own
     * Application — the only question the inline create button asks. Fails
     * closed when the form declares no applicationCode(), so a form can
     * never become creatable-from-anywhere by omission.
     */
    public function authorizeOutOfContext(): bool
    {
        $code = $this->applicationCode();

        if ($code === null) {
            return false;
        }

        return $this->authorize() && app(RecordReferenceAccess::class)->applicationAccessible(
            app(NavigationTreeService::class)->getApplicationByCode($code),
        );
    }

    /**
     * @param  array<string, mixed>  $data  Validated field values, keyed by each field's persisted column.
     */
    public function create(array $data): Model
    {
        $modelClass = $this->model();

        return $modelClass::create($data);
    }

    /**
     * @param  array<string, mixed>  $data  Validated field values, keyed by each field's persisted column.
     */
    public function update(Model $record, array $data): Model
    {
        $record->fill($data)->save();

        return $record;
    }

    /**
     * The record this form is editing, resolved through the definition rather
     * than a bare find() so a subclass can narrow it the way a record view's
     * query() does. Null when the id resolves to nothing the actor may edit.
     */
    public function findForEdit(int|string $recordId): ?Model
    {
        $modelClass = $this->model();

        return $modelClass::query()->find($recordId);
    }

    /**
     * Whether this already-loaded record may be edited through this form.
     * Separate from authorize(), which only asks about reaching the form at
     * all: a posted Journal is reachable and readable but not editable.
     */
    public function authorizeUpdate(Model $record): bool
    {
        return true;
    }

    /**
     * Whether this record may be used as the template of a copy — reading its
     * values into a new, unsaved record. Defaults to the same answer as
     * editing it, so a form never leaks values through Copy that it refuses to
     * show through Edit; override where the two differ (a posted Journal is
     * not editable but is a perfectly good template for the next one).
     */
    public function authorizeCopy(Model $record): bool
    {
        return $this->authorizeUpdate($record);
    }

    /**
     * Creates the new record a Copy action saves. The form fields are already
     * in $data; $source is there for everything a copy needs that is not a
     * field on this form — a Journal's lines. Plain create() by default.
     *
     * @param  array<string, mixed>  $data  Validated field values, keyed by each field's persisted column.
     */
    public function createCopy(array $data, Model $source): Model
    {
        return $this->create($data);
    }

    /**
     * Where the record this form just wrote can be viewed, taken from its
     * Application's own reference provider so no form declares a route of its
     * own. Null when the Application has no provider — the caller then simply
     * stays where it is.
     */
    public function recordUrl(Model $record): ?string
    {
        $code = $this->applicationCode();

        return $code === null
            ? null
            : app(RecordReferenceRegistry::class)->resolve($code)?->url($record);
    }

    /** @return array<string, array<int, string>> Laravel validation rules keyed by persisted column. */
    public function validationRules(): array
    {
        $rules = [];

        foreach ($this->fields() as $field) {
            $field->validate();
            $rules[$this->columnFor($field)] = $field->getRules();
        }

        return $rules;
    }

    protected function columnFor(Field $field): string
    {
        return method_exists($field, 'getColumn') ? $field->getColumn() : $field->getKey();
    }
}
