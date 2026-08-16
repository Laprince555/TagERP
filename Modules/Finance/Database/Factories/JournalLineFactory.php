<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\Finance\Models\GeneralLedger\Journal;
use Modules\Finance\Models\GeneralLedger\JournalLine;
use Modules\General\Models\World\Currency;

/**
 * @extends Factory<JournalLine>
 */
class JournalLineFactory extends Factory
{
    protected $model = JournalLine::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'journal_id' => Journal::factory(),
            'line_number' => 1,
            'account_id' => Account::factory(),
            'currency_id' => Currency::factory(),
            'exchange_rate' => 1,
            'debit' => 0,
            'credit' => 0,
            'base_debit' => 0,
            'base_credit' => 0,
        ];
    }

    public function debit(string $amount): static
    {
        return $this->state(fn (): array => [
            'debit' => $amount,
            'base_debit' => $amount,
            'credit' => 0,
            'base_credit' => 0,
        ]);
    }

    public function credit(string $amount): static
    {
        return $this->state(fn (): array => [
            'credit' => $amount,
            'base_credit' => $amount,
            'debit' => 0,
            'base_debit' => 0,
        ]);
    }
}
