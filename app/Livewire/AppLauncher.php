<?php

namespace App\Livewire;

use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
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
            ->map(function (array $module): array {
                $applications = collect($module['sub_modules'])
                    ->flatMap(fn (array $subModule): array => $subModule['applications'])
                    ->all();

                return [
                    'id' => $module['id'],
                    'title' => (string) ($module['name'] ?? 'Untitled Module'),
                    'category' => (string) $module['code'],
                    'description' => (string) ($module['description'] ?? ''),
                    'icon' => (string) ($module['icon'] ?? 'squares-2x2'),
                    'route_name' => (string) ($module['route_name'] ?? ''),
                    'route' => (string) ($module['route'] ?? '#'),
                    'badge' => count($applications),
                    'is_active' => $module['is_active'],
                    'applications' => $applications,
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
                        ->flatMap(fn (array $application): array => [
                            (string) ($application['name'] ?? ''),
                            (string) ($application['description'] ?? ''),
                            (string) ($application['route_name'] ?? ''),
                            (string) ($application['route'] ?? ''),
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
}
