<?php

namespace Modules\Finance\Database\Factories;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\GeneralLedger\ExchangeRate;
use Modules\Finance\Models\GeneralLedger\RateType;
use Modules\General\Models\World\Currency;

/**
 * @extends Factory<ExchangeRate>
 */
class ExchangeRateFactory extends Factory
{
    protected $model = ExchangeRate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'from_currency_id' => Currency::factory(),
            'to_currency_id' => Currency::factory(),
            'rate_date' => CarbonImmutable::create(2026, 1, 1),
            'rate' => fake()->randomFloat(6, 0.5, 60),
            'rate_type' => RateType::Daily,
        ];
    }
}
