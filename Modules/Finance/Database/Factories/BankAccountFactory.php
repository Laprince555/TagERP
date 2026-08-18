<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\CashAndBanks\Banks\Bank;
use Modules\Finance\Models\CashAndBanks\Banks\BankAccount;
use Modules\General\Models\World\Currency;

class BankAccountFactory extends Factory
{
    protected $model = BankAccount::class;

    public function definition(): array
    {
        return [
            'bank_id' => Bank::factory(),
            'account_number' => fake()->numerify('###########'),
            'account_name' => fake()->word().' Account',
            'account_type' => fake()->randomElement(['checking', 'savings', 'fixed']),
            'gl_account_id' => null,
            'currency_id' => Currency::factory(),
            'balance' => fake()->numberBetween(1000, 100000),
            'is_active' => true,
        ];
    }
}
