<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\GeneralLedger\Chart;

/**
 * @extends Factory<Chart>
 */
class ChartFactory extends Factory
{
    protected $model = Chart::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'levels_count' => 5,
            'is_active' => true,
        ];
    }
}
