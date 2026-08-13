<?php

namespace App\Livewire;

use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['showSidebar' => false, 'showBreadcrumbs' => false])]
class AppLauncher extends Component
{
    public string $search = '';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getModulesProperty(): array
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
                ]));

                return str_contains($haystack, $search);
            })
            ->values()
            ->all();
    }

    public function render(): View
    {
        $user = auth()->user();

        return view('livewire.app-launcher', [
            'modules' => $this->modules,
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
        foreach ($keys as $key) {
            $value = $this->arrayValue($target, [$key]);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return $default;
    }

    /**
     * @param  array<int, string>  $keys
     */
    private function arrayValue(mixed $target, array $keys): mixed
    {
        foreach ($keys as $key) {
            $segments = explode('.', $key);
            $value = $target;

            foreach ($segments as $segment) {
                if (is_array($value) && array_key_exists($segment, $value)) {
                    $value = $value[$segment];
                    continue;
                }

                if (is_object($value) && isset($value->{$segment})) {
                    $value = $value->{$segment};
                    continue;
                }

                continue 2;
            }

            return $value;
        }

        return null;
    }
}
