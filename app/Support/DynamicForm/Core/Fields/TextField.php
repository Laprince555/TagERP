<?php

namespace App\Support\DynamicForm\Core\Fields;

use App\Support\DynamicForm\Core\Field;

class TextField extends Field
{
    protected string $inputType = 'text';

    /** @param  'text'|'email'|'tel'|'url'|'password'  $type */
    public function type(string $type): static
    {
        $this->inputType = $type;

        return $this;
    }

    public function getInputType(): string
    {
        return $this->inputType;
    }

    public function component(): string
    {
        return 'dynamic-form.fields.text';
    }
}
