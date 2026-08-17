<?php

namespace Modules\Finance\Models\GeneralLedger;

/**
 * How far a journal book's documents travel.
 *
 * `Selected` with nothing selected is the useful edge: a document type that
 * stays in the primary ledger only — a management adjustment the tax books must
 * never see. It needs no third case of its own.
 */
enum LedgerScope: string
{
    case All = 'all';
    case Selected = 'selected';

    public function label(): string
    {
        return match ($this) {
            self::All => __('Every secondary ledger'),
            self::Selected => __('Only the selected ledgers'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $scope) {
            $options[$scope->value] = $scope->label();
        }

        return $options;
    }
}
