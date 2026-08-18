<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\CashAndBanks\PaymentDisburse\PaymentDisbursementRequest;
use Modules\General\Models\World\Currency;
use Modules\HR\Models\OrganizationStructure\Entity;

class PaymentDisbursementRequestFactory extends Factory
{
    protected $model = PaymentDisbursementRequest::class;

    public function definition(): array
    {
        return [
            'number' => fake()->unique()->numerify('PDR-#####'),
            'entity_id' => Entity::factory(),
            'payment_date' => fake()->date(),
            'amount' => fake()->randomFloat(2, 100, 10000),
            'currency_id' => Currency::factory(),
            'description' => fake()->text(),
            'status' => 'draft',
            'payment_method' => fake()->randomElement(['bank_transfer', 'check', 'cash']),
        ];
    }
}
