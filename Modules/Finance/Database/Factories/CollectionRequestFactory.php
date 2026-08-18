<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\CashAndBanks\Collection\CollectionRequest;
use Modules\General\Models\World\Currency;
use Modules\HR\Models\OrganizationStructure\Entity;

class CollectionRequestFactory extends Factory
{
    protected $model = CollectionRequest::class;

    public function definition(): array
    {
        return [
            'number' => fake()->unique()->numerify('COL-#####'),
            'entity_id' => Entity::factory(),
            'expected_date' => fake()->futureDate(),
            'amount' => fake()->randomFloat(2, 100, 10000),
            'currency_id' => Currency::factory(),
            'description' => fake()->text(),
            'status' => 'pending',
            'collection_method' => fake()->randomElement(['bank_transfer', 'check', 'cash', 'other']),
        ];
    }
}
