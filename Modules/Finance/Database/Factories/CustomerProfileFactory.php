<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CRM\Models\Customers\Customer;
use Modules\Finance\Models\AccountsReceivable\CustomerFinancialRole;
use Modules\Finance\Models\AccountsReceivable\CustomerProfile;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\General\Models\World\Currency;

/**
 * @extends Factory<CustomerProfile>
 */
class CustomerProfileFactory extends Factory
{
    protected $model = CustomerProfile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'financial_role' => fake()->randomElement(CustomerFinancialRole::cases())->value,
            'currency_id' => Currency::factory(),
            'ar_account_id' => Account::factory(),
            'credit_terms_days' => fake()->numberBetween(0, 90),
            'credit_limit' => fake()->randomFloat(2, 10000, 1000000),
            'is_active' => true,
        ];
    }
}
