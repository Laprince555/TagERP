<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\AccountsPayable\ApInvoice;
use Modules\Finance\Models\AccountsPayable\ApInvoiceTaxLine;
use Modules\Finance\Models\Tax\Tax;

/**
 * @extends Factory<ApInvoiceTaxLine>
 */
class ApInvoiceTaxLineFactory extends Factory
{
    protected $model = ApInvoiceTaxLine::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => ApInvoice::factory(),
            'tax_id' => Tax::factory(),
            'cost_center_id' => null,
            'taxable_amount' => fake()->randomFloat(2, 100, 5000),
            'tax_amount' => fake()->randomFloat(2, 10, 500),
        ];
    }
}
