<?php

namespace Modules\CRM\Models\Customers;

/**
 * A customer's general classification. A customer can still carry several
 * Customer Financial Profiles in Finance/AR with a more specific financial
 * role — this is the coarse CRM-side label used for customer intake.
 */
enum CustomerType: string
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

        foreach (self::cases() as $type) {
            $options[$type->value] = $type->label();
        }

        return $options;
    }
}
