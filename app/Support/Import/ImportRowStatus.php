<?php

namespace App\Support\Import;

/**
 * Per-row outcome of a queued import. Every staged row starts Pending and
 * ends in exactly one terminal state, so the import page can show progress
 * while the job is still running.
 */
enum ImportRowStatus: string
{
    case Pending = 'pending';
    case Imported = 'imported';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Pending'),
            self::Imported => __('Imported'),
            self::Failed => __('Failed'),
        };
    }
}
