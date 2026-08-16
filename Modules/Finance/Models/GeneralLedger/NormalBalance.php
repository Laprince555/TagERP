<?php

namespace Modules\Finance\Models\GeneralLedger;

/**
 * Which side increases an account. Always derived from AccountNature, never
 * stored, so it can never drift out of sync with the nature it belongs to.
 */
enum NormalBalance: string
{
    case Debit = 'debit';
    case Credit = 'credit';

    public function label(): string
    {
        return match ($this) {
            self::Debit => __('Debit'),
            self::Credit => __('Credit'),
        };
    }
}
