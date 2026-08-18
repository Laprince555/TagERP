<?php

namespace Modules\HR\Database\Factories\Cycles;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HR\Models\Cycles\CycleException;
use Modules\HR\Models\Cycles\CycleExceptionMode;
use Modules\HR\Models\Cycles\CycleExceptionType;
use Modules\HR\Models\Cycles\CycleLine;
use Modules\HR\Models\OrganizationStructure\JobTitle;

/**
 * @extends Factory<CycleException>
 */
class CycleExceptionFactory extends Factory
{
    protected $model = CycleException::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cycle_line_id' => CycleLine::factory(),
            'job_title_id' => JobTitle::factory(),
            'job_grade_id' => null,
            'exception_type' => fake()->randomElement(CycleExceptionType::cases())->value,
            'condition' => ['max_amount' => fake()->numberBetween(1000, 10000)],
            'mode' => fake()->randomElement(CycleExceptionMode::cases())->value,
            'is_active' => true,
        ];
    }
}
