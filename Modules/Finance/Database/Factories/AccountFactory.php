<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\Finance\Models\GeneralLedger\AccountCategory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'number' => (string) fake()->unique()->numberBetween(100000, 999999),
            'parent_id' => null,
            'category_id' => AccountCategory::factory(),
            'required_analysis_type' => null,
            'is_active' => true,
        ];
    }

    public function childOf(Account $parent): static
    {
        return $this->state(fn (): array => [
            'parent_id' => $parent->getKey(),
            'category_id' => $parent->category_id,
        ]);
    }
}
