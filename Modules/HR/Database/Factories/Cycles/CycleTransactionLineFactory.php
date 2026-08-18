<?php

namespace Modules\HR\Database\Factories\Cycles;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HR\Models\Cycles\CycleLine;
use Modules\HR\Models\Cycles\CycleTransaction;
use Modules\HR\Models\Cycles\CycleTransactionLine;
use Modules\HR\Models\Cycles\CycleTransactionLineStatus;
use Modules\HR\Models\OrganizationStructure\JobTitle;

/**
 * @extends Factory<CycleTransactionLine>
 */
class CycleTransactionLineFactory extends Factory
{
    protected $model = CycleTransactionLine::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $word = fake()->unique()->word();

        return [
            'cycle_transaction_id' => CycleTransaction::factory(),
            'cycle_line_id' => CycleLine::factory(),
            'sequence' => fake()->unique()->numberBetween(1, 100),
            'name' => ['ar' => $word, 'en' => $word],
            'job_title_id' => JobTitle::factory(),
            'job_grade_id' => null,
            'target_status_on_approve' => null,
            'target_status_on_reject' => 'rejected',
            'status' => CycleTransactionLineStatus::Pending->value,
            'acted_by' => null,
            'acted_at' => null,
            'used_exception_id' => null,
            'note' => null,
        ];
    }
}
