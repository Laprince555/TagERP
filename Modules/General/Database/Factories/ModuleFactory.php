<?php

namespace Modules\General\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\General\System\Module;

/**
 * @extends Factory<Module>
 */
class ModuleFactory extends Factory
{
    protected $model = Module::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $slug = fake()->unique()->slug(2);

        return [
            'name' => ['en' => fake()->words(2, true), 'ar' => 'وحدة '.fake()->word()],
            'description' => ['en' => fake()->sentence(), 'ar' => 'وصف الوحدة'],
            'code' => $slug,
            'route' => $slug,
            'icon' => 'squares-2x2',
            'sort_order' => 0,
            'permission_group' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['is_active' => false]);
    }
}
