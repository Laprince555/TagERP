<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\CashAndBanks\Banks\Bank;
use Modules\Finance\Models\CashAndBanks\Categories\BankCategory;
use Modules\HR\Models\OrganizationStructure\Entity;

class BankFactory extends Factory
{
    protected $model = Bank::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'entity_id' => Entity::factory(),
            'category_id' => BankCategory::factory(),
            'default_gl_account_id' => null,
            'bank_code' => strtoupper(fake()->bothify('??')),
            'bank_name' => fake()->company().' Bank',
            'swift_code' => strtoupper(fake()->bothify('????????')),
            'iban' => fake()->iban(),
            'is_active' => true,
        ];
    }
}
