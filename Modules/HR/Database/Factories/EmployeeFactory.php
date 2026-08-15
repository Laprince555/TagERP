<?php

namespace Modules\HR\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\General\Database\Factories\PersonFactory;
use Modules\HR\Models\EmployeeManagement\Employee;
use Modules\HR\Models\OrganizationStructure\Branch;
use Modules\HR\Models\OrganizationStructure\Department;
use Modules\HR\Models\OrganizationStructure\Entity;
use Modules\HR\Models\OrganizationStructure\JobGrade;
use Modules\HR\Models\OrganizationStructure\JobTitle;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // A coherent chain (entity → branch → department active for that
        // entity → job title) is built by default so every employee record
        // satisfies the organizational integrity rules out of the box.
        $entity = Entity::factory()->create();
        $branch = Branch::factory()->main()->for($entity)->create();
        $department = Department::factory()->create();
        $department->attachToEntity($entity);
        $jobTitle = JobTitle::factory()->for($department)->create();
        $jobGrade = JobGrade::factory()->create();
        $jobTitle->jobGrades()->attach($jobGrade->id);

        return [
            'employee_number' => fake()->unique()->numerify('EMP-#####'),
            'person_id' => PersonFactory::new(),
            'user_id' => null,
            'entity_id' => $entity->id,
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'job_title_id' => $jobTitle->id,
            'job_grade_id' => $jobGrade->id,
            'entity_scope' => 'branch',
            'department_scope' => 'department',
            'gross_salary' => fake()->randomFloat(2, 5000, 50000),
            'status' => 'active',
            'hire_date' => fake()->dateTimeBetween('-5 years', 'now'),
            'termination_date' => null,
        ];
    }

    public function terminated(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'terminated',
            'termination_date' => fake()->dateTimeBetween('-1 year', 'now'),
        ]);
    }
}
