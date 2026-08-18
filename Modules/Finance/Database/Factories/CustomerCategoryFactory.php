<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\AccountsReceivable\CustomerCategory;

/**
 * @extends Factory<CustomerCategory>
 */
class CustomerCategoryFactory extends Factory
{
    protected $model = CustomerCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'description' => fake()->text(),
            'is_active' => true,
        ];
    }
}
