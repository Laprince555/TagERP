<?php

namespace App\Support\DynamicForm\Core;

use App\Services\NavigationTreeService;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Model;

/**
 * Base class for one Dynamic Form definition (create-only in this pass —
 * see App\Livewire\DynamicForm\Form). Framework-agnostic — MUST NOT import
 * Modules\* classes, Livewire, or Blade. A concrete subclass describes one
 * createable record type: its model, its fields, and what happens after a
 * successful save.
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
