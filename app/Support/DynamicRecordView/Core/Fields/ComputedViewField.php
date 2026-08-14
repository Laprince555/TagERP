<?php

namespace App\Support\DynamicRecordView\Core\Fields;

/**
 * A field with no backing attribute — its entire value comes from
 * formatUsing()/using(). Unlike other fields, resolveValue() passes the
 * whole record through so the callback can combine multiple attributes.
 */
class ComputedViewField extends Field
{
    /** @var (callable(mixed): mixed)|null */
    protected $using = null;

    /**
     * @param  callable(mixed $record): mixed  $callback
     */
    public function using(callable $callback): static
    {
        $this->using = $callback;

        return $this;
    }

    public function resolveValue(mixed $record): mixed
    {
        return $this->using ? ($this->using)($record) : null;
    }
}
