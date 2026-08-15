<?php

namespace App\Support\DynamicForm\Core\Fields;

use App\Support\DynamicForm\Core\Field;

class DateField extends Field
{
    public function component(): string
    {
        return 'dynamic-form.fields.date';
    }
}
