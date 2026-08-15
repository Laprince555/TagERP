<?php

namespace Modules\General\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\General\Models\World\People\Gender;
use Modules\General\Models\World\People\Person;

/**
 * @extends Factory<Person>
 */
class PersonFactory extends Factory
{
    protected $model = Person::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'nickname' => fake()->firstName(),
            'passport_number' => fake()->unique()->numerify('P########'),
            'national_id' => fake()->unique()->numerify('##############'),
            'city_id' => null,
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'bank_account_1' => fake()->numerify('##########'),
            'iban_1' => fake()->iban(),
            'bank_account_2' => null,
            'iban_2' => null,
            'date_of_birth' => fake()->date(),
            'gender' => fake()->randomElement(Gender::cases())->value,
        ];
    }
}
