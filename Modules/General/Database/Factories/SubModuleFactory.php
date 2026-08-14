<?php

namespace Modules\General\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\General\System\Module;
use Modules\General\System\SubModule;

/**
 * @extends Factory<SubModule>
 */
class SubModuleFactory extends Factory
{
    protected $model = SubModule::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $slug = fake()->unique()->slug(2);

        return [
            'name' => ['en' => fake()->words(2, true), 'ar' => 'وحدة فرعية '.fake()->word()],
            'description' => ['en' => fake()->sentence(), 'ar' => 'وصف الوحدة الفرعية'],
            'code' => $slug,
            'route' => $slug.'.index',
            'icon' => 'cpu-chip',
            'sort_order' => 0,
            'permission_group' => null,
            'is_active' => true,
            'module_id' => Module::factory(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['is_active' => false]);
    }
}
