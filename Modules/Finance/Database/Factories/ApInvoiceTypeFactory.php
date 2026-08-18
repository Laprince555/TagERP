<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\AccountsPayable\ApInvoiceType;

/**
 * @extends Factory<ApInvoiceType>
 */
class ApInvoiceTypeFactory extends Factory
{
    protected $model = ApInvoiceType::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
