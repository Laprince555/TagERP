<?php

namespace Modules\Finance\Models\GeneralLedger;

/**
 * Which rate a conversion should reach for. Transactions use the daily rate,
 * period-end balance work uses the closing rate, and income-statement
 * translation uses the period average.
 */
enum RateType: string
{
    case Daily = 'daily';
    case Closing = 'closing';
    case Average = 'average';

    public function label(): string
    {
        return match ($this) {
            self::Daily => __('Daily'),
            self::Closing => __('Closing'),
            self::Average => __('Average'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $type) {
            $options[$type->value] = $type->label();
        }

        return $options;
    }
}
