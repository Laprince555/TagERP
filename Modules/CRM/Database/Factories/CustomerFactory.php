<?php

namespace Modules\CRM\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CRM\Models\Customers\Customer;
use Modules\CRM\Models\Customers\CustomerType;
use Modules\Finance\Models\AccountsReceivable\CustomerCategory;
use Modules\General\Models\World\Companies\Company;
use Modules\General\Models\World\Currency;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'customer_category_id' => CustomerCategory::factory(),
            'customer_type' => fake()->randomElement(CustomerType::cases())->value,
            'default_currency_id' => Currency::factory(),
            'is_active' => true,
        ];
    }
}
