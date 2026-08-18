<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\AccountsPayable\ApInvoice;
use Modules\Finance\Models\AccountsPayable\ApInvoiceLine;

/**
 * @extends Factory<ApInvoiceLine>
 */
class ApInvoiceLineFactory extends Factory
{
    protected $model = ApInvoiceLine::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => ApInvoice::factory(),
            'description' => fake()->sentence(4),
            'quantity' => fake()->randomFloat(2, 1, 20),
            'unit_price' => fake()->randomFloat(2, 10, 500),
        ];
    }
}
