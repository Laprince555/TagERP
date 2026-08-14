<?php

namespace App\Support\RecordReference;

/**
 * On-demand preview payload. `available` is false for any unauthorized,
 * missing, inactive, or deleted record — callers must show a generic
 * unavailable state, never a partial identity/facts leak.
 *
 * @param  RecordFact[]  $facts
 */
final readonly class RecordReferencePreview
{
    /**
     * @param  array<int, array{label: string, value: mixed}>  $facts
     */
    public function __construct(
        public bool $available,
        public ?string $status = null,
        public ?string $title = null,
        public ?string $url = null,
        public array $facts = [],
    ) {}

    public static function unavailable(): self
    {
        return new self(available: false);
    }

    public function toArray(): array
    {
        return [
            'available' => $this->available,
            'status' => $this->status,
            'title' => $this->title,
            'url' => $this->url,
            'facts' => $this->facts,
        ];
    }
}
