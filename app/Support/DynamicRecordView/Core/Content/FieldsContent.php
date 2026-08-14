<?php

namespace App\Support\DynamicRecordView\Core\Content;

use App\Support\DynamicRecordView\Core\Exceptions\DuplicateFieldKeyException;
use App\Support\DynamicRecordView\Core\Fields\Field;

/**
 * A grid of typed read-only fields ("Basic Information"-style panels).
 */
class FieldsContent extends Content
{
    /** @var array<string, Field> */
    protected array $fields = [];

    protected ?string $heading = null;

    public function heading(string $heading): static
    {
        $this->heading = $heading;

        return $this;
    }

    public function getHeading(): ?string
    {
        return $this->heading;
    }

    /**
     * @param  Field[]  $fields
     */
    public function fields(array $fields): static
    {
        foreach ($fields as $field) {
            if (isset($this->fields[$field->getKey()])) {
                throw DuplicateFieldKeyException::forKey($field->getKey());
            }

            $this->fields[$field->getKey()] = $field;
        }

        return $this;
    }

    /**
     * @return Field[]
     */
    public function getFields(): array
    {
        return array_values($this->fields);
    }
}
