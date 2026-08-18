<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\AccountsPayable\ApInvoice;
use Modules\Finance\Models\AccountsPayable\ApInvoiceDeductionLine;
use Modules\Finance\Models\AccountsPayable\Deduction;

/**
 * @extends Factory<ApInvoiceDeductionLine>
 */
class ApInvoiceDeductionLineFactory extends Factory
{
    protected $model = ApInvoiceDeductionLine::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => ApInvoice::factory(),
            'deduction_id' => Deduction::factory(),
            'cost_center_id' => null,
            'amount' => fake()->randomFloat(2, 10, 1000),
        ];
    }
}
