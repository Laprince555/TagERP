<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\CashAndBanks\Banks\Bank;
use Modules\Finance\Models\CashAndBanks\InternalAdjust\InternalAdjust;
use Modules\General\Models\World\Currency;

class InternalAdjustFactory extends Factory
{
    protected $model = InternalAdjust::class;

    public function definition(): array
    {
        return [
            'number' => null,
            'from_bank_id' => Bank::factory(),
            'from_safe_id' => null,
            'to_bank_id' => Bank::factory(),
            'to_safe_id' => null,
            'adjustment_date' => fake()->dateTime(),
            'amount' => fake()->numberBetween(1000, 50000),
            'currency_id' => Currency::factory(),
            'description' => fake()->optional()->sentence(),
            'reference' => fake()->optional()->word(),
            'status' => 'draft',
            'posted_at' => null,
            'journal_id' => null,
        ];
    }
}
