<?php

namespace Modules\Finance\Models\GeneralLedger;

/**
 * The five root account natures. Closed set on purpose: normal balance and
 * statement placement are derived from the nature, so a user-defined sixth
 * value would have no defined behaviour anywhere in reporting.
 */
enum AccountNature: string
{
    case Asset = 'asset';
    case Liability = 'liability';
    case Equity = 'equity';
    case Revenue = 'revenue';
    case Expense = 'expense';

    public function normalBalance(): NormalBalance
    {
        return match ($this) {
            self::Asset, self::Expense => NormalBalance::Debit,
            self::Liability, self::Equity, self::Revenue => NormalBalance::Credit,
        };
    }

    public function statement(): FinancialStatement
    {
        return match ($this) {
            self::Asset, self::Liability, self::Equity => FinancialStatement::BalanceSheet,
            self::Revenue, self::Expense => FinancialStatement::IncomeStatement,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Asset => __('Asset'),
            self::Liability => __('Liability'),
            self::Equity => __('Equity'),
            self::Revenue => __('Revenue'),
            self::Expense => __('Expense'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $nature) {
            $options[$nature->value] = $nature->label();
        }

        return $options;
    }
}
