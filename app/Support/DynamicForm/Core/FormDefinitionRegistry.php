<?php

namespace App\Support\DynamicForm\Core;

use InvalidArgumentException;

/**
 * Immutable-after-boot formKey -> definition-class mapping, exactly mirroring
 * App\Support\DynamicRecordView\Core\RecordViewRegistry. Modules register
 * their own DynamicForm subclasses from their ServiceProvider::boot().
 */
class FormDefinitionRegistry
{
    /** @var array<string, class-string<DynamicForm>> */
    protected array $forms = [];

    /** @param  class-string<DynamicForm>  $formClass */
    public function register(string $formKey, string $formClass): void
    {
        if (isset($this->forms[$formKey])) {
            throw new InvalidArgumentException("A Dynamic Form is already registered for key [{$formKey}].");
        }

        $this->forms[$formKey] = $formClass;
    }

    public function has(string $formKey): bool
    {
        return isset($this->forms[$formKey]);
    }

    public function resolve(string $formKey): DynamicForm
    {
        if (! isset($this->forms[$formKey])) {
            throw new InvalidArgumentException("No Dynamic Form registered for key [{$formKey}].");
        }

        /** @var DynamicForm $definition */
        $definition = app($this->forms[$formKey]);
        $definition->setFormKey($formKey);

        return $definition;
    }
}
