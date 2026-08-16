<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\GeneralLedger\JournalBook;

/**
 * @extends Factory<JournalBook>
 */
class JournalBookFactory extends Factory
{
    protected $model = JournalBook::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'sequence_prefix' => strtoupper(fake()->unique()->lexify('???')),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
