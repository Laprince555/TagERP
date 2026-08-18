<?php

namespace Modules\HR\Database\Factories\Cycles;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HR\Models\Cycles\Cycle;
use Modules\HR\Models\Cycles\CycleType;

/**
 * @extends Factory<Cycle>
 */
class CycleFactory extends Factory
{
    protected $model = Cycle::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $word = fake()->unique()->word();

        return [
            'cycle_type_id' => CycleType::factory(),
            'name' => ['ar' => $word, 'en' => $word],
            'description' => fake()->optional()->sentence(),
            'subject_model' => 'App\\Models\\DummySubject',
            'document_type_value' => null,
            'is_active' => true,
        ];
    }
}
