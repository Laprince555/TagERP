@props([
    'application',
    'module' => null,
    'subModule' => null,
])

@php
    $appModel = $application instanceof \Modules\General\System\Application ? $application : null;
    $appName = is_array($application) ? ($application['name'] ?? '') : ($application->name ?? '');
    $appDesc = is_array($application) ? ($application['description'] ?? '') : ($application->description ?? '');
    $appIcon = (is_array($application) ? ($application['icon'] ?? null) : ($application->icon ?? null)) ?: 'squares-2x2';
    $appCode = is_array($application) ? ($application['code'] ?? '') : ($application->code ?? '');

    $subModuleObj = $subModule ?? ($appModel?->subModule ?? null);
    $subModuleName = is_array($subModuleObj) ? ($subModuleObj['name'] ?? '') : ($subModuleObj?->name ?? '');
    $subModuleCode = is_array($subModuleObj) ? ($subModuleObj['code'] ?? '') : ($subModuleObj?->code ?? '');
    $subModuleRouteName = is_array($subModuleObj) ? ($subModuleObj['route'] ?? null) : ($subModuleObj?->route ?? null);

    $resolveUrl = function (?string $routeName): ?string {
        if (blank($routeName) || $routeName === '#') {
            return null;
        }

        $route = \Illuminate\Support\Facades\Route::getRoutes()->getByName($routeName);
        if ($route === null) {
            return null;
        }

        $parameters = in_array('locale', $route->parameterNames(), true)
            ? ['locale' => app()->getLocale()]
            : [];

        try {
            return route($routeName, $parameters);
        } catch (\Throwable) {
            return null;
        }
    };

    $subModuleRoute = $resolveUrl($subModuleRouteName);
@endphp

<section class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8" {{ $attributes }}>
    @if (filled($subModuleRoute))
        <flux:button
            wire:navigate
            :href="$subModuleRoute"
            variant="ghost"
            size="sm"
            icon="arrow-left"
            class="mt-3 rtl:[&_svg]:rotate-180"
        >
            {{ __('messages.workspace.back_to_sub_module', ['name' => $subModuleName]) }}
        </flux:button>
    @endif

    <div class="mt-4 flex flex-col gap-5 rounded-[1.75rem] border border-[var(--color-glass-border)] bg-[var(--color-surface-1)] p-5 shadow-lg shadow-[var(--color-card-shadow)] sm:p-6 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex min-w-0 items-start gap-4">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-[linear-gradient(135deg,var(--color-primary-soft),var(--color-accent-soft))] text-[var(--color-primary)] shadow-inner">
                <flux:icon :name="$appIcon" class="h-7 w-7" />
            </div>

            <div class="min-w-0 space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                    <flux:heading size="xl" level="1" class="truncate">{{ $appName }}</flux:heading>

                    <flux:badge color="zinc" size="sm">{{ __('messages.workspace.application') }}</flux:badge>
                </div>

                <p class="line-clamp-2 max-w-2xl text-sm leading-6 text-[var(--color-soft-text)]">
                    {{ $appDesc ?: __('messages.workspace.no_description') }}
                </p>

                <p class="font-mono text-xs font-bold uppercase tracking-[0.16em] text-[var(--color-muted-text)]">{{ $appCode }}</p>
            </div>
        </div>

        @if (isset($actions))
            <div class="flex shrink-0 flex-wrap items-center gap-2">
                {{ $actions }}
            </div>
        @endif
    </div>
</section>
