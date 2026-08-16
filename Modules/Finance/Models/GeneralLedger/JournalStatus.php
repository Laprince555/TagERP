<?php

namespace Modules\Finance\Models\GeneralLedger;

/**
 * A journal's place in its life. There is no "edited" state on purpose: once
 * posted, a journal is corrected by a reversing journal, never in place.
 */
enum JournalStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Reversed = 'reversed';

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    public function isPosted(): bool
    {
        return $this === self::Posted || $this === self::Reversed;
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::Posted => __('Posted'),
            self::Reversed => __('Reversed'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $status) {
            $options[$status->value] = $status->label();
        }

        return $options;
    }
}
