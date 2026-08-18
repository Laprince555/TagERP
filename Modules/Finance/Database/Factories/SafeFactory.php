<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\CashAndBanks\Safes\Safe;
use Modules\HR\Models\OrganizationStructure\Entity;

class SafeFactory extends Factory
{
    protected $model = Safe::class;

    public function definition(): array
    {
        return [
            'name' => 'Safe #'.fake()->numerify('###'),
            'entity_id' => Entity::factory(),
            'employee_id' => null,
            'location' => fake()->optional()->address(),
            'description' => fake()->optional()->sentence(),
            'gl_account_id' => null,
            'is_active' => true,
        ];
    }
}
