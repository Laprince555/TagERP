<?php

namespace Modules\General\Database\Factories;

use App\Support\RecordReference\ApplicationColor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\General\System\Application;
use Modules\General\System\SubModule;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $slug = fake()->unique()->slug(2);

        return [
            'name' => ['en' => fake()->words(2, true), 'ar' => 'تطبيق '.fake()->word()],
            'description' => ['en' => fake()->sentence(), 'ar' => 'وصف التطبيق'],
            'code' => $slug,
            'route' => $slug.'.index',
            'icon' => 'squares-2x2',
            'color' => fake()->randomElement(ApplicationColor::cases())->value,
            'application_group' => null,
            'sort_order' => 0,
            'permission_name' => null,
            'permission_group' => null,
            'is_active' => true,
            'submodule_id' => SubModule::factory(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['is_active' => false]);
    }

    public function requiringPermission(string $permissionName): static
    {
        return $this->state(fn (array $attributes): array => ['permission_name' => $permissionName]);
    }
}
