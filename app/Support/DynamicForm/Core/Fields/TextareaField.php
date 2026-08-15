<?php

namespace App\Support\DynamicForm\Core\Fields;

use App\Support\DynamicForm\Core\Field;

class TextareaField extends Field
{
    protected int $rows = 3;

    public function rowsCount(int $rows): static
    {
        $this->rows = max(1, $rows);

        return $this;
    }

    public function getRows(): int
    {
        return $this->rows;
    }

    public function component(): string
    {
        return 'dynamic-form.fields.textarea';
    }
}
