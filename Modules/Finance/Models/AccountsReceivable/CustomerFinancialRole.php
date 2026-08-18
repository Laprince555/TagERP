<?php

namespace Modules\Finance\Models\AccountsReceivable;

/**
 * The financial role a Customer Profile routes AR invoices under. Distinct
 * from CRM's CustomerType: one customer can carry several profiles, each
 * with its own currency and ledger account, so the financial role can
 * diverge from the customer's general classification.
 */
enum CustomerFinancialRole: string
{
    case Retail = 'retail';
    case Wholesale = 'wholesale';
    case Corporate = 'corporate';

    public function label(): string
    {
        return match ($this) {
            self::Retail => __('Retail'),
            self::Wholesale => __('Wholesale'),
            self::Corporate => __('Corporate'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $role) {
            $options[$role->value] = $role->label();
        }

        return $options;
    }
}
