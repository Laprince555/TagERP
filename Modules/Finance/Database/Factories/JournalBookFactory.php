<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\GeneralLedger\JournalBook;
use Modules\Finance\Models\GeneralLedger\LedgerScope;

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
            'ledger_scope' => LedgerScope::All,
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }

    /** A document type that stays in the primary ledger only. */
    public function primaryLedgerOnly(): static
    {
        return $this->state(fn (): array => ['ledger_scope' => LedgerScope::Selected]);
    }
}
