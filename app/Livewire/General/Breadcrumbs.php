<?php

namespace App\Livewire\General;

use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Breadcrumbs extends Component
{
    /**
     * Appended as a final, non-linked crumb after the matched
     * Module/SubModule/Application chain — the record title on a
     * DynamicRecordView page (see record-view.blade.php), which the
     * navigation tree has no notion of.
     */
    public ?string $trailing = null;

    public function render(): View
    {
        return view('livewire.general.breadcrumbs', [
            'breadcrumbs' => $this->breadcrumbs(),
        ]);
    }

    /**
     * @return array<int, array{label: string, url: string|null, icon: string|null}>
     */
    protected function breadcrumbs(): array
    {
        $currentRoute = request()->route()?->getName();

        if (blank($currentRoute)) {
            return $this->withTrailing([]);
        }

        $tree = app(NavigationTreeService::class)->getTreeForUser();
        $routeSegments = explode('.', $currentRoute);

        foreach ($tree as $module) {
            $matchedBreadcrumbs = $this->matchModuleBreadcrumbs($module, $currentRoute, $routeSegments);

            if ($matchedBreadcrumbs !== []) {
                return $this->withTrailing($matchedBreadcrumbs);
            }
        }

        return $this->withTrailing([]);
    }

    /**
     * @param  array<int, array{label: string, url: string|null, icon: string|null}>  $breadcrumbs
     * @return array<int, array{label: string, url: string|null, icon: string|null}>
     */
    protected function withTrailing(array $breadcrumbs): array
    {
        if (blank($this->trailing)) {
            return $breadcrumbs;
        }

        return [...$breadcrumbs, ['label' => $this->trailing, 'url' => null, 'icon' => null]];
    }

    /**
     * @param  array<string, mixed>  $module
     * @param  array<int, string>  $routeSegments
     * @return array<int, array{label: string, url: string|null, icon: string|null}>
     *
     * Deepest match wins: an Application route ("general.world.countries")
     * is always a segment-prefix of its owning SubModule route
     * ("general.world"), which is in turn a prefix of its Module route
     * ("general"). routeMatches() is a prefix check, so it must be tried
     * application-first, then sub module, then module — checking the
     * module first would match on the shared "general" prefix and return
     * before ever looking at the sub module/application it actually
     * belongs to.
     */
    protected function matchModuleBreadcrumbs(array $module, string $currentRoute, array $routeSegments): array
    {
        $moduleBreadcrumb = [$this->makeBreadcrumb($module)];

        foreach ($module['sub_modules'] as $subModule) {
            $subModuleBreadcrumb = [...$moduleBreadcrumb, $this->makeBreadcrumb($subModule)];

            foreach ($subModule['applications'] as $application) {
                if ($this->routeMatches($currentRoute, $application['route_name'], $routeSegments)) {
                    return [...$subModuleBreadcrumb, $this->makeBreadcrumb($application)];
                }
            }

            if ($this->routeMatches($currentRoute, $subModule['route_name'], $routeSegments)) {
                return $subModuleBreadcrumb;
            }
        }

        if ($this->routeMatches($currentRoute, $module['route_name'], $routeSegments)) {
            return $moduleBreadcrumb;
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{label: string, url: string|null, icon: string|null}
     */
    protected function makeBreadcrumb(array $item): array
    {
        return [
            'label' => $item['name'] ?: 'Untitled',
            // 'route' holds the already-resolved, locale-aware URL built by
            // NavigationTreeService::resolveRoute() — reuse it instead of
            // calling route() again in the view (that route name needs a
            // {locale} parameter the view doesn't have).
            'url' => $item['route'] ?: null,
            'icon' => $item['icon'] ?: null,
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
}
