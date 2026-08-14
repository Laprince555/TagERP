<?php

namespace App\Support\RecordReference;

/**
 * Minimal initial payload for a record reference: enough to render the icon
 * chip, title, and link with zero secondary queries. Never carries preview
 * facts.
 */
final readonly class RecordReferenceIdentity
{
    public function __construct(
        public string $applicationCode,
        public string $applicationName,
        public ApplicationColor $applicationColor,
        public ?string $applicationIcon,
        public int|string $recordKey,
        public bool $authorized,
        public ?string $title = null,
        public ?string $url = null,
    ) {}
}
