<?php

namespace Modules\General\Livewire;

use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Shared workspace page for every ERP module.
 *
 * The rendered module is resolved from the current named route, which is matched
 * exactly against the `modules.route` column. The page is intentionally generic:
 * Finance, HR, CRM and friends all render through this single component.
 */
#[Layout('layouts.app')]
class ModuleWorkspace extends Component
{
    /**
     * Resolved server-side from the named route during mount, never from client input.
     */
    #[Locked]
    public int $moduleId = 0;

    public function mount(): void
    {
        $routeName = request()->route()?->getName();

        if (blank($routeName)) {
            abort(404);
        }

        $module = collect($this->navigationTree())
            ->first(fn (array $module): bool => $module['route_name'] === $routeName);

        abort_if($module === null, 404);

        $this->moduleId = (int) $module['id'];
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function module(): array
    {
        $module = collect($this->navigationTree())
            ->first(fn (array $module): bool => (int) $module['id'] === $this->moduleId);

        abort_if($module === null, 404);

        return $module;
    }

    /**
     * Active sub modules of the current module, ordered by `sort_order`.
     *
     * @return array<int, array{id: int, name: string, description: string, code: string, icon: string, url: ?string, application_count: int}>
     */
    #[Computed]
    public function subModules(): array
    {
        return collect($this->module['sub_modules'] ?? [])
            ->map(fn (array $subModule): array => [
                'id' => (int) $subModule['id'],
                'name' => (string) ($subModule['name'] ?? ''),
                'description' => (string) ($subModule['description'] ?? ''),
                'code' => (string) ($subModule['code'] ?? ''),
                'icon' => (string) ($subModule['icon'] ?: 'squares-2x2'),
                'url' => $subModule['is_active'] ? $subModule['route'] : null,
                'application_count' => count($subModule['applications'] ?? []),
            ])
            ->values()
            ->all();
    }

    /**
     * Structural counts only. Business metrics are deliberately left to a future iteration.
     *
     * @return array{sub_module_count: int, application_count: int}
     */
    #[Computed]
    public function statistics(): array
    {
        $subModules = $this->subModules;

        return [
            'sub_module_count' => count($subModules),
            'application_count' => (int) array_sum(array_column($subModules, 'application_count')),
        ];
    }

    public function render(): View
    {
        return view('general::livewire.module-workspace', [
            'module' => $this->module,
            'subModules' => $this->subModules,
            'statistics' => $this->statistics,
        ]);
    }

    /**
     * The navigation tree is cached per user, locale and roles, and already applies
     * active filtering, translation, route resolution and permission filtering.
     *
     * @return array<int, array<string, mixed>>
     */
    private function navigationTree(): array
    {
        return app(NavigationTreeService::class)->getTreeForUser();
    }
}
