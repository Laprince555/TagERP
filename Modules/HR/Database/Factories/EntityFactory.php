<?php

namespace Modules\HR\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\General\Database\Factories\CompanyFactory;
use Modules\HR\Models\OrganizationStructure\Entity;

/**
 * @extends Factory<Entity>
 */
class EntityFactory extends Factory
{
    protected $model = Entity::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'company_id' => CompanyFactory::new(),
            'parent_entity_id' => null,
            'is_holding' => false,
            'currency_id' => null,
            'fiscal_year_start_month' => 1,
            'fiscal_year_start_day' => 1,
            'legal_form' => fake()->randomElement(['LLC', 'JSC', 'Sole Proprietorship']),
            'ownership_percentage' => null,
            'tax_authority' => null,
            'board_members' => null,
            'is_active' => true,
        ];
    }

    public function childOf(Entity $parent): static
    {
        return $this->state(fn (array $attributes): array => [
            'parent_entity_id' => $parent->id,
            'ownership_percentage' => fake()->randomFloat(2, 51, 100),
        ]);
    }

    public function holding(): static
    {
        return $this->state(fn (array $attributes): array => ['is_holding' => true]);
    }
}
