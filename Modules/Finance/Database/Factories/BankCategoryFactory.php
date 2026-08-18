<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\CashAndBanks\Categories\BankCategory;

class BankCategoryFactory extends Factory
{
    protected $model = BankCategory::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'parent_id' => null,
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
