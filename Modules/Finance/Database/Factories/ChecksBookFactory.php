<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\CashAndBanks\Banks\Bank;
use Modules\Finance\Models\CashAndBanks\Banks\BankAccount;
use Modules\Finance\Models\CashAndBanks\Banks\ChecksBook;

class ChecksBookFactory extends Factory
{
    protected $model = ChecksBook::class;

    public function definition(): array
    {
        $start = fake()->numberBetween(1000, 5000);
        $end = $start + 99;

        return [
            'bank_id' => Bank::factory(),
            'bank_account_id' => BankAccount::factory(),
            'check_series_start' => $start,
            'check_series_end' => $end,
            'current_check_number' => $start,
            'status' => 'active',
            'is_active' => true,
        ];
    }
}
