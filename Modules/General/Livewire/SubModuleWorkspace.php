<?php

namespace Modules\General\Livewire;

use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Shared workspace page for every ERP sub module.
 *
 * The rendered sub module is resolved from the current named route, matched exactly
 * against the `sub_modules.route` column, so `general.world` and `finance.gl` both
 * render through this single component with no per-route branching.
 *
 * @see ModuleWorkspace for the module-level sibling page.
 */
#[Layout('layouts.app')]
class SubModuleWorkspace extends Component
{
    /**
     * Resolved server-side from the named route during mount, never from client input.
     */
    #[Locked]
    public int $subModuleId = 0;

    public function mount(): void
    {
        $routeName = request()->route()?->getName();

        if (blank($routeName)) {
            abort(404);
        }

        $context = $this->locate(
            fn (array $subModule): bool => ($subModule['route_name'] ?? null) === $routeName,
        );

        // Absent from the navigation tree means the route has no sub module behind it, or
        // the sub module (or its module) is inactive. Both are a 404, mirroring how the
        // module workspace treats an inactive module.
        abort_if($context === null, 404);

        $this->subModuleId = (int) $context['sub_module']['id'];
    }

    /**
     * @return array{module: array<string, mixed>, sub_module: array<string, mixed>}
     */
    #[Computed]
    public function context(): array
    {
        $context = $this->locate(
            fn (array $subModule): bool => (int) $subModule['id'] === $this->subModuleId,
        );

        abort_if($context === null, 404);

        return $context;
    }

    /**
     * Applications of the current sub module, ordered by `sort_order`.
     *
     * The navigation tree has already dropped inactive applications and the ones the
     * current user lacks permission for, so nothing further is filtered here. An
     * application whose route name is not registered yet resolves to a null url and
     * renders as an unavailable card rather than a dead link.
     *
     * @return array<int, array{id: int, name: string, description: string, code: string, icon: string, url: ?string}>
     */
    #[Computed]
    public function applications(): array
    {
        return collect($this->context['sub_module']['applications'] ?? [])
            ->map(fn (array $application): array => [
                'id' => (int) $application['id'],
                'name' => (string) ($application['name'] ?? ''),
                'description' => (string) ($application['description'] ?? ''),
                'code' => (string) ($application['code'] ?? ''),
                'icon' => (string) ($application['icon'] ?: 'squares-2x2'),
                'url' => ($application['route'] ?? '#') === '#' ? null : (string) $application['route'],
            ])
            ->values()
            ->all();
    }

    /**
     * Structural counts only. Task and completion metrics are deliberately left to a
     * future iteration and render as explicit placeholders.
     *
     * @return array{application_count: int, available_count: int}
     */
    #[Computed]
    public function statistics(): array
    {
        $applications = $this->applications;

        return [
            'application_count' => count($applications),
            'available_count' => count(array_filter(
                $applications,
                fn (array $application): bool => $application['url'] !== null,
            )),
        ];
    }

    public function render(): View
    {
        $context = $this->context;

        return view('general::livewire.sub-module-workspace', [
            'module' => $context['module'],
            'subModule' => $context['sub_module'],
            'applications' => $this->applications,
            'statistics' => $this->statistics,
        ]);
    }

    /**
     * Find a sub module and its owning module in the navigation tree.
     *
     * The tree is cached per user, locale and roles, and already applies active
     * filtering, translation, route resolution and permission filtering.
     *
     * @param  callable(array<string, mixed>): bool  $matches
     * @return array{module: array<string, mixed>, sub_module: array<string, mixed>}|null
     */
    private function locate(callable $matches): ?array
    {
        foreach (app(NavigationTreeService::class)->getTreeForUser() as $module) {
            foreach ($module['sub_modules'] ?? [] as $subModule) {
                if ($matches($subModule)) {
                    return ['module' => $module, 'sub_module' => $subModule];
                }
            }
        }

        return null;
    }
}
