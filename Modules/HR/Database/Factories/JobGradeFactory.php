<?php

namespace Modules\HR\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HR\Models\OrganizationStructure\JobGrade;

/**
 * @extends Factory<JobGrade>
 */
class JobGradeFactory extends Factory
{
    protected $model = JobGrade::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            // A wide range, not 1-100: several tests hardcode low levels
            // explicitly (e.g. junior=10, senior=30) via ->create(['level' =>
            // ...]), which bypasses Faker's own uniqueness bookkeeping — a
            // narrow default range risks the *unrelated* random pick landing
            // on the same value a test hardcoded elsewhere in the same run.
            'level' => fake()->unique()->numberBetween(1000, 30000),
            'is_active' => true,
        ];
    }
}
