<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\Tax\TaxCategory;
use Nnjeim\World\Models\Country;

/**
 * @extends Factory<TaxCategory>
 */
class TaxCategoryFactory extends Factory
{
    protected $model = TaxCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->optional()->sentence(),
            // ponytail: nnjeim/world's Country has no factory; reuse/seed one row instead of a hardcoded id.
            'country_id' => fn () => Country::query()->value('id')
                ?? Country::create(['iso2' => 'EG', 'name' => 'Egypt', 'status' => 1])->id,
            'is_active' => true,
        ];
    }
}
