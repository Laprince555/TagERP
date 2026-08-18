<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\AccountsPayable\Deduction;
use Modules\Finance\Models\AccountsPayable\DeductionCategory;

/**
 * @extends Factory<Deduction>
 */
class DeductionFactory extends Factory
{
    protected $model = Deduction::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'deduction_category_id' => DeductionCategory::factory(),
            'calculation_type' => 'fixed',
            'value' => fake()->randomFloat(2, 0, 1000),
            'is_active' => true,
        ];
    }
}
