<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\GeneralLedger\AccountGroup;
use Modules\Finance\Models\GeneralLedger\AccountGroupPurpose;

/**
 * @extends Factory<AccountGroup>
 */
class AccountGroupFactory extends Factory
{
    protected $model = AccountGroup::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'purpose' => AccountGroupPurpose::Access,
            'is_active' => true,
        ];
    }

    public function template(): static
    {
        return $this->state(fn (): array => ['purpose' => AccountGroupPurpose::Template]);
    }
}
