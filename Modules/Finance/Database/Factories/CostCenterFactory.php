<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\GeneralLedger\CostCenter;

/**
 * @extends Factory<CostCenter>
 */
class CostCenterFactory extends Factory
{
    protected $model = CostCenter::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'number' => (string) fake()->unique()->numberBetween(100000, 999999),
            'parent_id' => null,
            'accepts_transactions' => true,
            'is_active' => true,
        ];
    }

    public function childOf(CostCenter $parent): static
    {
        return $this->state(fn (): array => ['parent_id' => $parent->getKey()]);
    }
}
