<?php

namespace Modules\Finance\Models\AccountsPayable;

/**
 * The financial role a Vendor Profile routes AP invoices under. Distinct
 * from Procurement's VendorType: one vendor can carry several profiles, each
 * with its own currency and ledger account, so the financial role can
 * diverge from the vendor's general classification.
 */
enum VendorFinancialRole: string
{
    case Supplier = 'supplier';
    case Subcontractor = 'subcontractor';
    case ServiceProvider = 'service_provider';

    public function label(): string
    {
        return match ($this) {
            self::Supplier => __('Supplier'),
            self::Subcontractor => __('Subcontractor'),
            self::ServiceProvider => __('Service Provider'),
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
