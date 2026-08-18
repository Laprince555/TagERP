<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\AccountsPayable\VendorFinancialRole;
use Modules\Finance\Models\AccountsPayable\VendorProfile;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\General\Models\World\Currency;
use Modules\Procurement\Models\Vendors\Vendor;

/**
 * @extends Factory<VendorProfile>
 */
class VendorProfileFactory extends Factory
{
    protected $model = VendorProfile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'financial_role' => fake()->randomElement(VendorFinancialRole::cases())->value,
            'currency_id' => Currency::factory(),
            'ap_account_id' => Account::factory(),
            'payment_terms_days' => fake()->numberBetween(0, 90),
            'is_active' => true,
        ];
    }
}
