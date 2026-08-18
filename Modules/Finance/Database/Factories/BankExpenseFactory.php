<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\CashAndBanks\BankExpenses\BankExpense;
use Modules\Finance\Models\CashAndBanks\Banks\Bank;
use Modules\Finance\Models\CashAndBanks\Banks\BankAccount;
use Modules\General\Models\World\Currency;

class BankExpenseFactory extends Factory
{
    protected $model = BankExpense::class;

    public function definition(): array
    {
        return [
            'number' => null,
            'bank_id' => Bank::factory(),
            'bank_account_id' => BankAccount::factory(),
            'expense_date' => fake()->dateTime(),
            'amount' => fake()->numberBetween(10, 500),
            'currency_id' => Currency::factory(),
            'expense_type' => fake()->randomElement(['service_fee', 'interest', 'commission']),
            'description' => fake()->optional()->sentence(),
            'invoice_reference' => fake()->optional()->word(),
            'gl_account_id' => null,
            'status' => 'draft',
            'posted_at' => null,
            'journal_id' => null,
            'reconciliation_status' => 'unreconciled',
        ];
    }
}
