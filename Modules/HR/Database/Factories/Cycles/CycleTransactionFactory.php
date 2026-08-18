<?php

namespace Modules\HR\Database\Factories\Cycles;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HR\Models\Cycles\Cycle;
use Modules\HR\Models\Cycles\CycleTransaction;
use Modules\HR\Models\Cycles\CycleTransactionStatus;

/**
 * @extends Factory<CycleTransaction>
 */
class CycleTransactionFactory extends Factory
{
    protected $model = CycleTransaction::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cycle_id' => Cycle::factory(),
            'subject_type' => 'App\\Models\\DummySubject',
            'subject_id' => 1,
            'status' => CycleTransactionStatus::InProgress->value,
            'started_by' => User::factory(),
            'started_at' => now(),
            'completed_at' => null,
        ];
    }
}
