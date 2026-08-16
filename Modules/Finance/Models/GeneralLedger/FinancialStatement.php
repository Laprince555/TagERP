<?php

namespace Modules\Finance\Models\GeneralLedger;

/**
 * Which statement an account rolls up into. Derived from AccountNature.
 */
enum FinancialStatement: string
{
    case BalanceSheet = 'balance_sheet';
    case IncomeStatement = 'income_statement';

    public function label(): string
    {
        return match ($this) {
            self::BalanceSheet => __('Balance Sheet'),
            self::IncomeStatement => __('Income Statement'),
        };
    }
}
