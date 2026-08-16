<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\GeneralLedger\AccountCategory;
use Modules\Finance\Models\GeneralLedger\AccountNature;

/**
 * @extends Factory<AccountCategory>
 */
class AccountCategoryFactory extends Factory
{
    protected $model = AccountCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'nature' => fake()->randomElement(AccountNature::cases()),
            'sort_order' => fake()->numberBetween(0, 50),
            'is_active' => true,
        ];
    }

    public function nature(AccountNature $nature): static
    {
        return $this->state(fn (): array => ['nature' => $nature]);
    }
}
