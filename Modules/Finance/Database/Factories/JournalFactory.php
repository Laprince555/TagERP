<?php

namespace Modules\Finance\Database\Factories;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\GeneralLedger\FiscalPeriod;
use Modules\Finance\Models\GeneralLedger\Journal;
use Modules\Finance\Models\GeneralLedger\JournalBook;
use Modules\Finance\Models\GeneralLedger\Ledger;

/**
 * @extends Factory<Journal>
 */
class JournalFactory extends Factory
{
    protected $model = Journal::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ledger_id' => Ledger::factory(),
            'journal_book_id' => JournalBook::factory(),
            'fiscal_period_id' => FiscalPeriod::factory(),
            'journal_date' => CarbonImmutable::create(2026, 1, 15),
            'description' => fake()->sentence(),
        ];
    }

    /**
     * A journal sitting in a period of the given ledger's own entity, dated
     * inside that period — the shape almost every test needs.
     */
    public function inPeriod(Ledger $ledger, FiscalPeriod $period): static
    {
        return $this->state(fn (): array => [
            'ledger_id' => $ledger->getKey(),
            'fiscal_period_id' => $period->getKey(),
            'journal_date' => $period->start_date,
        ]);
    }
}
