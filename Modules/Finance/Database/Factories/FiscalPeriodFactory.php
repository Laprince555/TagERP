<?php

namespace Modules\Finance\Database\Factories;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\GeneralLedger\FiscalPeriod;
use Modules\Finance\Models\GeneralLedger\FiscalYear;

/**
 * @extends Factory<FiscalPeriod>
 */
class FiscalPeriodFactory extends Factory
{
    protected $model = FiscalPeriod::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = CarbonImmutable::create(2026, 1, 1);

        return [
            'name' => $start->format('M Y'),
            'fiscal_year_id' => FiscalYear::factory(),
            'sequence' => 1,
            'start_date' => $start,
            'end_date' => $start->endOfMonth(),
            'is_adjustment' => false,
        ];
    }
}
