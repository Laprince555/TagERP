<?php

namespace App\Support\DynamicForm\Core;

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
     * Extra, form-level authorization beyond the caller's own Application
     * gate (e.g. a business rule). True by default.
     */
    public function authorize(): bool
    {
        return true;
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
