<?php

namespace Modules\Finance\Models\GeneralLedger;

/**
 * How a secondary ledger differs from the primary it is fed from.
 */
enum LedgerConversionType: string
{
    case Currency = 'currency';
    case Chart = 'chart';
    case Both = 'both';

    /**
     * Currency conversion is what introduces rounding differences, so it is
     * also what makes a rounding account mandatory.
     */
    public function convertsCurrency(): bool
    {
        return $this === self::Currency || $this === self::Both;
    }

    public function label(): string
    {
        return match ($this) {
            self::Currency => __('Currency'),
            self::Chart => __('Chart of Accounts'),
            self::Both => __('Currency and Chart'),
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
