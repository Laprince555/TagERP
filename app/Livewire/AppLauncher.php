<?php

namespace App\Livewire;

use App\Services\NavigationTreeService;
use App\Support\FallbackValue;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['showSidebar' => false, 'showBreadcrumbs' => false])]
class AppLauncher extends Component
{
    public string $search = '';

    /**
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function modules(): array
    {
        $tree = app(NavigationTreeService::class)->getTreeForUser();
        $search = mb_strtolower(trim($this->search));

        return collect($tree)
            ->map(function (mixed $module): array {
                $title = $this->value($module, ['title', 'name.en', 'name', 'label'], 'Untitled Module');
                $category = $this->value($module, ['category', 'group', 'section', 'code'], 'Module');
                $description = $this->value($module, ['description.en', 'description', 'summary'], '');
                $icon = $this->value($module, ['icon'], 'squares-2x2');
                $route = $this->value($module, ['route', 'url'], '#');
                $applications = $this->moduleApplications($module);

                return [
                    'id' => $this->value($module, ['id', 'code', 'key'], $title),
                    'title' => (string) $title,
                    'category' => (string) $category,
                    'description' => (string) $description,
                    'icon' => (string) $icon,
                    'route_name' => (string) $this->value($module, ['route_name'], ''),
                    'route' => (string) $route,
                    'badge' => $applications->count(),
                    'is_active' => (bool) $this->value($module, ['is_active', 'active'], true),
                    'applications' => $applications->all(),
                ];
            })
            ->filter(function (array $module) use ($search): bool {
                if ($search === '') {
                    return true;
                }

                $haystack = mb_strtolower(implode(' ', [
                    $module['title'],
                    $module['category'],
                    $module['description'],
                    $module['route_name'],
                    $module['route'],
                    ...collect($module['applications'])
                        ->flatMap(fn (mixed $application): array => [
                            (string) $this->value($application, ['name'], ''),
                            (string) $this->value($application, ['description'], ''),
                            (string) $this->value($application, ['route_name'], ''),
                            (string) $this->value($application, ['route'], ''),
                        ])
                        ->all(),
                ]));

                return str_contains($haystack, $search);
            })
            ->values()
            ->all();
    }

    public function clearSearch(): void
    {
        $this->search = '';
    }

    public function render(): View
    {
        $user = auth()->user();
        $modules = $this->modules;

        return view('livewire.app-launcher', [
            'modules' => $modules,
            'visibleModuleCount' => count($modules),
            'applicationTotal' => collect($modules)->sum('badge'),
            'enabledModules' => collect($modules)->where('is_active', true)->count(),
            'userName' => $user?->name ?? '',
            'userRole' => $user?->getRoleNames()->first() ?? '',
        ]);
    }

    /**
     * @return Collection<int, mixed>
     */
    private function moduleApplications(mixed $module): Collection
    {
        $applications = $this->arrayValue($module, ['applications', 'apps', 'children']);

        if ($applications instanceof Collection) {
            return $applications;
        }

        if (is_array($applications)) {
            return collect($applications);
        }

        $subModules = $this->arrayValue($module, ['sub_modules', 'subModules']);

        if ($subModules instanceof Collection) {
            return $subModules
                ->flatMap(fn (mixed $subModule): array => $this->subModuleApplications($subModule)->all())
                ->values();
        }

        if (is_array($subModules)) {
            return collect($subModules)
                ->flatMap(fn (mixed $subModule): array => $this->subModuleApplications($subModule)->all())
                ->values();
        }

        return collect();
    }

    /**
     * @return Collection<int, mixed>
     */
    private function subModuleApplications(mixed $subModule): Collection
    {
        $applications = $this->arrayValue($subModule, ['applications', 'apps', 'children']);

        if ($applications instanceof Collection) {
            return $applications;
        }

        if (is_array($applications)) {
            return collect($applications);
        }

        return collect();
    }

    /**
     * @param  array<int, string>  $keys
     */
    private function value(mixed $target, array $keys, mixed $default = null): mixed
    {
        return FallbackValue::get($target, $keys, $default);
    }

    /**
     * @param  array<int, string>  $keys
     */
    private function arrayValue(mixed $target, array $keys): mixed
    {
        foreach ($keys as $key) {
            $value = FallbackValue::path($target, $key);

            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }
}
