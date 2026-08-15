<?php

namespace App\Support\DynamicForm\Core\Fields;

use App\Support\DynamicForm\Core\Field;

class SelectField extends Field
{
    /** @var array<string, string> value => label */
    protected array $options = [];

    /** @param  array<string, string>  $options */
    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    /** @return array<string, string> */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function component(): string
    {
        return 'dynamic-form.fields.select';
    }
}
