<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\CashAndBanks\Collection\CollectionDetail;
use Modules\Finance\Models\CashAndBanks\Collection\CollectionRequest;

class CollectionDetailFactory extends Factory
{
    protected $model = CollectionDetail::class;

    public function definition(): array
    {
        return [
            'collection_request_id' => CollectionRequest::factory(),
            'payer_type' => 'customer',
            'amount' => fake()->randomFloat(2, 100, 5000),
            'description' => fake()->text(),
            'collection_status' => 'pending',
        ];
    }
}
