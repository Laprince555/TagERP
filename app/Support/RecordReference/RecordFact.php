<?php

namespace App\Support\RecordReference;

/**
 * One allowlisted, already-escaped-at-render-time label/value pair. Label
 * and value are plain scalars — Blade escapes them on output, callers must
 * never mark them as HTML-safe.
 */
final readonly class RecordFact
{
    public function __construct(
        public string $label,
        public string|int|float|null $value,
        public int $priority = 0,
    ) {}
}
