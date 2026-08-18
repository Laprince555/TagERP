<?php

namespace Modules\HR\Models\Cycles;

/**
 * One cycle-transaction run's overall state.
 */
enum CycleTransactionStatus: string
{
    case InProgress = 'in_progress';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function isOpen(): bool
    {
        return $this === self::InProgress;
    }

    public function label(): string
    {
        return match ($this) {
            self::InProgress => __('In Progress'),
            self::Approved => __('Approved'),
            self::Rejected => __('Rejected'),
            self::Cancelled => __('Cancelled'),
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
