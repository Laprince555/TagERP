<?php

namespace Modules\General\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\General\Models\World\People\Person;
use Modules\General\Models\World\People\PersonPosition;

/**
 * @extends Factory<PersonPosition>
 */
class PersonPositionFactory extends Factory
{
    protected $model = PersonPosition::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'person_id' => Person::factory(),
            'company_id' => null,
            'position' => fake()->jobTitle(),
            'start_date' => fake()->date(),
            'end_date' => null,
            'is_current' => true,
        ];
    }
}
