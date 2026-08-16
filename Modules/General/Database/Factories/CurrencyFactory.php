<?php

namespace Modules\General\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\General\Models\World\Currency;

/**
 * Currencies normally arrive from the world seeder, which is far too heavy for
 * a test that just needs something to denominate an amount in.
 *
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = strtoupper(fake()->unique()->lexify('???'));

        return [
            'country_id' => 1,
            'name' => $code.' Currency',
            'code' => $code,
            'precision' => 2,
            'symbol' => $code,
            'symbol_native' => $code,
            'symbol_first' => 1,
            'decimal_mark' => '.',
            'thousands_separator' => ',',
        ];
    }
}
