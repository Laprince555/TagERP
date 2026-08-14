<div
    data-module-code="{{ $module['code'] }}"
    class="min-h-screen bg-[radial-gradient(circle_at_top_left,var(--color-primary-soft),transparent_28%),linear-gradient(180deg,var(--color-surface-0),var(--color-canvas-bg))]"
>
    <section class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
        <flux:button
            href="{{ route('launcher') }}"
            variant="ghost"
            size="sm"
            icon="arrow-left"
            class="rtl:[&_svg]:rotate-180"
        >
            {{ __('messages.workspace.back_to_launcher') }}
        </flux:button>

        <div class="mt-4 flex flex-col gap-5 rounded-[1.75rem] border border-[var(--color-glass-border)] bg-[var(--color-surface-1)] p-5 shadow-lg shadow-[var(--color-card-shadow)] sm:p-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex min-w-0 items-start gap-4">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-[linear-gradient(135deg,var(--color-primary-soft),var(--color-accent-soft))] text-[var(--color-primary)] shadow-inner">
                    <flux:icon :name="$module['icon'] ?: 'squares-2x2'" class="h-7 w-7" />
                </div>

                <div class="min-w-0 space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:heading size="xl" class="truncate">{{ $module['name'] }}</flux:heading>

                        <flux:badge :color="$module['is_active'] ? 'lime' : 'rose'" size="sm">
                            {{ $module['is_active'] ? __('messages.workspace.active') : __('messages.workspace.inactive') }}
                        </flux:badge>
                    </div>

                    <p class="line-clamp-2 max-w-2xl text-sm leading-6 text-[var(--color-soft-text)]">
                        {{ $module['description'] ?: __('messages.workspace.no_description') }}
                    </p>

                    <p class="font-mono text-xs font-bold uppercase tracking-[0.16em] text-[var(--color-muted-text)]">{{ $module['code'] }}</p>
                </div>
            </div>

            <div class="flex shrink-0 flex-wrap items-center gap-2">
                <flux:badge color="zinc" size="sm">
                    <span>{{ $statistics['sub_module_count'] }}</span>
                    <span class="ms-1">{{ __('messages.workspace.sub_modules') }}</span>
                </flux:badge>

                <flux:badge color="zinc" size="sm">
                    <span>{{ $statistics['application_count'] }}</span>
                    <span class="ms-1">{{ __('messages.workspace.applications') }}</span>
                </flux:badge>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8" aria-label="{{ __('messages.workspace.overview') }}">
        <div class="grid gap-4 lg:grid-cols-3">
            <x-general::workspace.pending-tasks-card class="lg:col-span-1" />

            <div class="grid gap-4 sm:grid-cols-2 lg:col-span-2 lg:content-start">
                <x-general::workspace.stat-tile
                    :label="__('messages.workspace.sub_modules')"
                    :value="$statistics['sub_module_count']"
                    icon="rectangle-group"
                    :hint="__('messages.workspace.sub_modules_hint')"
                />

                <x-general::workspace.stat-tile
                    :label="__('messages.workspace.applications')"
                    :value="$statistics['application_count']"
                    icon="squares-2x2"
                    :hint="__('messages.workspace.applications_hint')"
                />

                <x-general::workspace.stat-tile
                    :label="__('messages.workspace.activity')"
                    icon="chart-bar"
                    coming-soon
                />

                <x-general::workspace.stat-tile
                    :label="__('messages.workspace.alerts')"
                    icon="bell-alert"
                    coming-soon
                />
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
        <div class="mb-5 flex items-end justify-between gap-4">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.26em] text-[var(--color-muted-text)]">{{ __('messages.workspace.module') }}</p>
                <flux:heading size="lg" class="mt-2">{{ __('messages.workspace.sub_modules') }}</flux:heading>
            </div>

            <p class="shrink-0 text-sm text-[var(--color-soft-text)]">
                {{ trans_choice('messages.workspace.sub_module_count', $statistics['sub_module_count'], ['count' => $statistics['sub_module_count']]) }}
            </p>
        </div>

        @if ($subModules !== [])
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($subModules as $subModule)
                    <x-general::workspace.sub-module-card
                        wire:key="workspace-sub-module-{{ $subModule['id'] }}"
                        :sub-module="$subModule"
                    />
                @endforeach
            </div>
        @else
            <div class="rounded-[1.75rem] border border-dashed border-[var(--color-glass-border)] bg-[var(--color-surface-1)]/78 px-6 py-14 text-center shadow-lg shadow-[var(--color-card-shadow)]">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-[linear-gradient(135deg,var(--color-primary-soft),var(--color-accent-soft))] text-[var(--color-primary)]">
                    <flux:icon name="rectangle-group" class="h-8 w-8" />
                </div>

                <flux:heading size="lg" class="mt-5">{{ __('messages.workspace.empty_title') }}</flux:heading>

                <p class="mx-auto mt-3 max-w-xl text-sm leading-7 text-[var(--color-soft-text)]">
                    {{ __('messages.workspace.empty_description') }}
                </p>

                <div class="mt-6 flex justify-center">
                    <flux:button href="{{ route('launcher') }}" variant="primary" icon="squares-2x2">
                        {{ __('messages.workspace.back_to_launcher') }}
                    </flux:button>
                </div>
            </div>
        @endif
    </section>
</div>
