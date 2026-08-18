<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\Tax\Tax;
use Modules\Finance\Models\Tax\TaxAdjustment;

/**
 * @extends Factory<TaxAdjustment>
 */
class TaxAdjustmentFactory extends Factory
{
    protected $model = TaxAdjustment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tax_id' => Tax::factory(),
            'adjustment_date' => now()->toDateString(),
            'reason' => 'settlement',
            'amount' => fake()->randomFloat(2, 10, 5000),
            'description' => fake()->optional()->sentence(),
            'status' => 'draft',
        ];
    }
}
