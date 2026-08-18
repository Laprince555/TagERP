<?php

namespace Modules\HR\Models\Cycles;

/**
 * Whether an authorized exception skips the stage outright (Bypass) or lets
 * someone else act in the assigned approver's place (Delegate).
 */
enum CycleExceptionMode: string
{
    case Bypass = 'bypass';
    case Delegate = 'delegate';

    public function label(): string
    {
        return match ($this) {
            self::Bypass => __('Bypass'),
            self::Delegate => __('Delegate'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $mode) {
            $options[$mode->value] = $mode->label();
        }

        return $options;
    }
}
