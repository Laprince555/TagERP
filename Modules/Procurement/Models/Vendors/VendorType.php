<?php

namespace Modules\Procurement\Models\Vendors;

/**
 * A vendor's general classification. A vendor can still carry several
 * Vendor Financial Profiles in Finance/AP with a more specific financial
 * role — this is the coarse Procurement-side label used for vendor intake.
 */
enum VendorType: string
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

        foreach (self::cases() as $type) {
            $options[$type->value] = $type->label();
        }

        return $options;
    }
}
