<?php

namespace Modules\Finance\Database\Factories;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\GeneralLedger\FiscalYear;
use Modules\HR\Models\OrganizationStructure\Entity;

/**
 * @extends Factory<FiscalYear>
 */
class FiscalYearFactory extends Factory
{
    protected $model = FiscalYear::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = CarbonImmutable::create(fake()->unique()->numberBetween(2000, 2099), 1, 1);

        return [
            'name' => 'FY '.$start->year,
            'entity_id' => Entity::factory(),
            'start_date' => $start,
            'end_date' => $start->addYear()->subDay(),
            'is_active' => true,
        ];
    }

    public function startingOn(CarbonImmutable $start): static
    {
        return $this->state(fn (): array => [
            'name' => 'FY '.$start->year,
            'start_date' => $start,
            'end_date' => $start->addYear()->subDay(),
        ]);
    }
}
