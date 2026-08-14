<?php

namespace App\Livewire\General;

use App\Services\NavigationTreeService;
use App\Support\FallbackValue;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Breadcrumbs extends Component
{
    public function render(): View
    {
        return view('livewire.general.breadcrumbs', [
            'breadcrumbs' => $this->breadcrumbs(),
        ]);
    }

    /**
     * @return array<int, array{label: string, route: string|null, icon: string|null}>
     */
    protected function breadcrumbs(): array
    {
        $currentRoute = request()->route()?->getName();

        if (blank($currentRoute)) {
            return [];
        }

        $tree = app(NavigationTreeService::class)->getTreeForUser();
        $routeSegments = explode('.', $currentRoute);

        foreach ($tree as $module) {
            $matchedBreadcrumbs = $this->matchModuleBreadcrumbs($module, $currentRoute, $routeSegments);

            if ($matchedBreadcrumbs !== []) {
                return $matchedBreadcrumbs;
            }
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $module
     * @param  array<int, string>  $routeSegments
     * @return array<int, array{label: string, route: string|null, icon: string|null}>
     */
    protected function matchModuleBreadcrumbs(array $module, string $currentRoute, array $routeSegments): array
    {
        $moduleRoute = $this->stringValue($module, 'route');
        $moduleBreadcrumb = [$this->makeBreadcrumb($module)];

        if ($this->routeMatches($currentRoute, $moduleRoute, $routeSegments)) {
            return $moduleBreadcrumb;
        }

        foreach ($this->arrayValue($module, 'sub_modules') as $subModule) {
            if (! is_array($subModule)) {
                continue;
            }

            $subModuleRoute = $this->stringValue($subModule, 'route');
            $subModuleBreadcrumb = [...$moduleBreadcrumb, $this->makeBreadcrumb($subModule)];

            if ($this->routeMatches($currentRoute, $subModuleRoute, $routeSegments)) {
                return $subModuleBreadcrumb;
            }

            foreach ($this->arrayValue($subModule, 'applications') as $application) {
                if (! is_array($application)) {
                    continue;
                }

                $applicationRoute = $this->stringValue($application, 'route');

                if ($this->routeMatches($currentRoute, $applicationRoute, $routeSegments)) {
                    return [...$subModuleBreadcrumb, $this->makeBreadcrumb($application)];
                }
            }
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{label: string, route: string|null, icon: string|null}
     */
    protected function makeBreadcrumb(array $item): array
    {
        return [
            'label' => $this->stringValue($item, 'name', 'Untitled'),
            'route' => $this->stringValue($item, 'route') ?: null,
            'icon' => $this->stringValue($item, 'icon') ?: null,
        ];
    }

    /**
     * @param  array<int, string>  $currentSegments
     */
    protected function routeMatches(string $currentRoute, ?string $candidateRoute, array $currentSegments): bool
    {
        if (blank($candidateRoute)) {
            return false;
        }

        if ($currentRoute === $candidateRoute) {
            return true;
        }

        $candidateSegments = explode('.', $candidateRoute);
        $candidateLength = count($candidateSegments);

        if ($candidateLength > count($currentSegments)) {
            return false;
        }

        return array_slice($currentSegments, 0, $candidateLength) === $candidateSegments;
    }

    /**
     * @return array<int, mixed>
     */
    protected function arrayValue(array $item, string $key): array
    {
        $value = FallbackValue::path($item, $key, []);

        return is_array($value) ? $value : [];
    }

    protected function stringValue(array $item, string $key, string $default = ''): string
    {
        $value = FallbackValue::get($item, [$key], $default);

        return is_string($value) && $value !== '' ? $value : $default;
    }
}
