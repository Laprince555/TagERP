<?php

namespace Modules\HR\Database\Factories\Cycles;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HR\Models\Cycles\Cycle;
use Modules\HR\Models\Cycles\CycleLine;
use Modules\HR\Models\OrganizationStructure\JobTitle;

/**
 * @extends Factory<CycleLine>
 */
class CycleLineFactory extends Factory
{
    protected $model = CycleLine::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $word = fake()->unique()->word();

        return [
            'cycle_id' => Cycle::factory(),
            'sequence' => fake()->unique()->numberBetween(1, 100),
            'name' => ['ar' => $word, 'en' => $word],
            'job_title_id' => JobTitle::factory(),
            'job_grade_id' => null,
            'target_status_on_approve' => null,
            'target_status_on_reject' => 'rejected',
        ];
    }
}
