<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\CashAndBanks\PaymentDisburse\PaymentDisbursementDetail;
use Modules\Finance\Models\CashAndBanks\PaymentDisburse\PaymentDisbursementRequest;

class PaymentDisbursementDetailFactory extends Factory
{
    protected $model = PaymentDisbursementDetail::class;

    public function definition(): array
    {
        return [
            'payment_disbursement_request_id' => PaymentDisbursementRequest::factory(),
            'payee_type' => 'employee',
            'amount' => fake()->randomFloat(2, 100, 5000),
            'description' => fake()->text(),
        ];
    }
}
