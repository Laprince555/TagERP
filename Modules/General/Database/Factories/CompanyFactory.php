<?php

namespace Modules\General\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\General\Models\World\Companies\Company;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'tax_id' => fake()->unique()->numerify('##########'),
            'commercial_registration' => fake()->numerify('CR-#######'),
            'city_id' => null,
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->companyEmail(),
            'logo' => null,
            'bank_account_1' => fake()->numerify('##########'),
            'iban_1' => fake()->iban(),
            'bank_account_2' => null,
            'iban_2' => null,
            'website' => fake()->url(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['is_active' => false]);
    }
}
