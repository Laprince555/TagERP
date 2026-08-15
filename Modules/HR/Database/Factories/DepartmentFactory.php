<?php

namespace Modules\HR\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HR\Models\OrganizationStructure\Department;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->jobTitle().' Department',
            'parent_department_id' => null,
            'is_active' => true,
        ];
    }

    public function childOf(Department $parent): static
    {
        return $this->state(fn (array $attributes): array => [
            'parent_department_id' => $parent->id,
        ]);
    }
}
