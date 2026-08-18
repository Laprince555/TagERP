<?php

namespace Modules\HR\Database\Factories\Cycles;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HR\Models\Cycles\CycleType;

/**
 * @extends Factory<CycleType>
 */
class CycleTypeFactory extends Factory
{
    protected $model = CycleType::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $word = fake()->unique()->word();

        return [
            'application_code' => 'fin-ap-inv',
            'name' => ['ar' => $word, 'en' => $word],
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
