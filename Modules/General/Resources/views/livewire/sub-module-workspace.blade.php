<div class="min-h-screen bg-[radial-gradient(circle_at_top_left,var(--color-primary-soft),transparent_28%),linear-gradient(180deg,var(--color-surface-0),var(--color-canvas-bg))]">
    <section class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
        <flux:breadcrumbs aria-label="{{ __('messages.workspace.breadcrumb') }}" class="flex-wrap gap-y-1">
            <flux:breadcrumbs.item :href="route('launcher')" icon="home" />

            @if ($module['route'] !== '#')
                <flux:breadcrumbs.item :href="$module['route']">{{ $module['name'] }}</flux:breadcrumbs.item>
            @else
                <flux:breadcrumbs.item>{{ $module['name'] }}</flux:breadcrumbs.item>
            @endif

            <flux:breadcrumbs.item aria-current="page">{{ $subModule['name'] }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        @if ($module['route'] !== '#')
            <flux:button
                :href="$module['route']"
                variant="ghost"
                size="sm"
                icon="arrow-left"
                class="mt-3 rtl:[&_svg]:rotate-180"
            >
                {{ __('messages.workspace.back_to_module', ['name' => $module['name']]) }}
            </flux:button>
        @endif

        <div class="mt-4 flex flex-col gap-5 rounded-[1.75rem] border border-[var(--color-glass-border)] bg-[var(--color-surface-1)] p-5 shadow-lg shadow-[var(--color-card-shadow)] sm:p-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex min-w-0 items-start gap-4">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-[linear-gradient(135deg,var(--color-primary-soft),var(--color-accent-soft))] text-[var(--color-primary)] shadow-inner">
                    <flux:icon :name="$subModule['icon'] ?: 'squares-2x2'" class="h-7 w-7" />
                </div>

                <div class="min-w-0 space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:heading size="xl" level="1" class="truncate">{{ $subModule['name'] }}</flux:heading>

                        <flux:badge color="zinc" size="sm">{{ __('messages.workspace.sub_module') }}</flux:badge>
                    </div>

                    <p class="line-clamp-2 max-w-2xl text-sm leading-6 text-[var(--color-soft-text)]">
                        {{ $subModule['description'] ?: __('messages.workspace.no_description') }}
                    </p>

                    <p class="font-mono text-xs font-bold uppercase tracking-[0.16em] text-[var(--color-muted-text)]">{{ $subModule['code'] }}</p>
                </div>
            </div>

            <div class="flex shrink-0 flex-wrap items-center gap-2">
                <flux:badge color="zinc" size="sm">
                    <span>{{ $statistics['application_count'] }}</span>
                    <span class="ms-1">{{ __('messages.workspace.applications') }}</span>
                </flux:badge>

                <flux:badge color="lime" size="sm">
                    <span>{{ $statistics['available_count'] }}</span>
                    <span class="ms-1">{{ __('messages.workspace.available') }}</span>
                </flux:badge>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8" aria-label="{{ __('messages.workspace.sub_module_overview') }}">
        <div class="grid gap-4 lg:grid-cols-3">
            <x-general::workspace.pending-tasks-card
                class="lg:col-span-1"
                :hint="__('messages.workspace.pending_tasks_sub_module_hint')"
            />

            <div class="grid gap-4 sm:grid-cols-2 lg:col-span-2 lg:content-start">
                <x-general::workspace.stat-tile
                    :label="__('messages.workspace.applications')"
                    :value="$statistics['application_count']"
                    icon="squares-2x2"
                    :hint="__('messages.workspace.applications_hint')"
                />

                <x-general::workspace.stat-tile
                    :label="__('messages.workspace.available')"
                    :value="$statistics['available_count']"
                    icon="rocket-launch"
                    :hint="__('messages.workspace.available_hint')"
                />

                <x-general::workspace.stat-tile
                    :label="__('messages.workspace.pending_tasks')"
                    icon="clipboard-document-check"
                    coming-soon
                />

                <x-general::workspace.stat-tile
                    :label="__('messages.workspace.completion_rate')"
                    icon="chart-pie"
                    coming-soon
                />
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
        <div class="mb-5 flex items-end justify-between gap-4">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.26em] text-[var(--color-muted-text)]">{{ __('messages.workspace.sub_module') }}</p>
                <flux:heading size="lg" level="2" class="mt-2">{{ __('messages.workspace.applications') }}</flux:heading>
            </div>

            <p class="shrink-0 text-sm text-[var(--color-soft-text)]">
                {{ trans_choice('messages.workspace.application_count', $statistics['application_count'], ['count' => $statistics['application_count']]) }}
            </p>
        </div>

        @if ($applications !== [])
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($applications as $application)
                    <x-general::workspace.application-card
                        wire:key="workspace-application-{{ $application['id'] }}"
                        :application="$application"
                    />
                @endforeach
            </div>
        @else
            <div class="rounded-[1.75rem] border border-dashed border-[var(--color-glass-border)] bg-[var(--color-surface-1)]/78 px-6 py-14 text-center shadow-lg shadow-[var(--color-card-shadow)]">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-[linear-gradient(135deg,var(--color-primary-soft),var(--color-accent-soft))] text-[var(--color-primary)]">
                    <flux:icon name="squares-2x2" class="h-8 w-8" />
                </div>

                <flux:heading size="lg" class="mt-5">{{ __('messages.workspace.applications_empty_title') }}</flux:heading>

                <p class="mx-auto mt-3 max-w-xl text-sm leading-7 text-[var(--color-soft-text)]">
                    {{ __('messages.workspace.applications_empty_description') }}
                </p>

                @if ($module['route'] !== '#')
                    <div class="mt-6 flex justify-center">
                        <flux:button :href="$module['route']" variant="primary" icon="rectangle-group">
                            {{ __('messages.workspace.back_to_module', ['name' => $module['name']]) }}
                        </flux:button>
                    </div>
                @endif
            </div>
        @endif
    </section>
</div>
