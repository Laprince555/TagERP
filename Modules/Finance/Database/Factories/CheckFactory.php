<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\CashAndBanks\Banks\Bank;
use Modules\Finance\Models\CashAndBanks\Banks\Check;
use Modules\Finance\Models\CashAndBanks\Banks\ChecksBook;

class CheckFactory extends Factory
{
    protected $model = Check::class;

    public function definition(): array
    {
        return [
            'checks_book_id' => ChecksBook::factory(),
            'bank_id' => Bank::factory(),
            'check_number' => fake()->numberBetween(1000, 9999),
            'check_date' => fake()->dateTime(),
            'amount' => fake()->numberBetween(1000, 50000),
            'payee_name' => fake()->name(),
            'description' => fake()->optional()->sentence(),
            'status' => 'written',
            'journal_id' => null,
        ];
    }
}
