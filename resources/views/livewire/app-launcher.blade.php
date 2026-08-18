<div class="min-h-screen bg-[radial-gradient(circle_at_top_left,var(--color-primary-soft),transparent_30%),radial-gradient(circle_at_top_right,var(--color-accent-soft),transparent_28%),linear-gradient(180deg,var(--color-surface-0),var(--color-canvas-bg))]">
    <section class="relative overflow-hidden border-b border-[var(--color-glass-border)]">
        <div class="absolute inset-0 bg-[linear-gradient(120deg,color-mix(in_srgb,var(--color-primary)_12%,transparent),transparent_38%,color-mix(in_srgb,var(--color-accent)_10%,transparent))]"></div>
        <div class="relative mx-auto flex max-w-7xl flex-col gap-8 px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
            <div class="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl space-y-5">
                    <div class="inline-flex items-center gap-3 rounded-full border border-[var(--color-glass-border)] bg-[var(--color-surface-1)]/80 px-4 py-2 shadow-sm backdrop-blur">
                        <span class="h-2.5 w-2.5 rounded-full bg-[var(--color-success)] shadow-[0_0_18px_var(--color-success)]"></span>
                        <p class="text-xs font-black uppercase tracking-[0.28em] text-[var(--color-soft-text)]">
                            Enterprise Workspace
                        </p>
                    </div>

                    <div class="space-y-4">
                        <h1 class="font-[Instrument_Sans] text-4xl font-black tracking-[-0.04em] text-[var(--color-text-main)] sm:text-5xl lg:text-6xl">
                            Welcome to TAGSERP Suite
                        </h1>
                        <p class="max-w-2xl text-sm leading-7 text-[var(--color-soft-text)] sm:text-base">
                            Launch modules, discover business tools, and move through your workspace from one polished command center.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <div class="badge badge-ledger-in">
                            <span>{{ $visibleModuleCount }}</span>
                            <span class="ml-1">visible modules</span>
                        </div>

                        <div class="badge badge-neutral">
                            <span>{{ $applicationTotal }}</span>
                            <span class="ml-1">applications</span>
                        </div>

                        <div class="badge badge-allocated">
                            <span>{{ $enabledModules }}</span>
                            <span class="ml-1">enabled</span>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:w-[28rem]">
                    <div class="rounded-3xl border border-[var(--color-glass-border)] bg-[var(--color-surface-1)] p-5 shadow-xl shadow-[var(--color-card-shadow)] backdrop-blur">
                        <p class="text-xs font-black uppercase tracking-[0.24em] text-[var(--color-muted-text)]">Signed in</p>
                        <p class="mt-3 truncate text-lg font-bold text-[var(--color-text-main)]">{{ $userName !== '' ? $userName : 'Workspace User' }}</p>
                        <p class="mt-1 text-sm text-[var(--color-soft-text)]">{{ $userRole !== '' ? $userRole : 'Team member' }}</p>
                    </div>

                    <div class="rounded-3xl border border-[var(--color-glass-border)] bg-[var(--color-panel-strong)] p-5 text-[var(--color-text-main)] shadow-xl shadow-[var(--color-card-shadow)]">
                        <p class="text-xs font-black uppercase tracking-[0.24em] text-[var(--color-muted-text)]">Fast launch</p>
                        <p class="mt-3 text-lg font-bold">Search, filter, open</p>
                        <p class="mt-1 text-sm text-[var(--color-soft-text)]">Use the live search below to narrow modules instantly.</p>
                    </div>
                </div>
            </div>

            <div class="relative max-w-4xl">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-5 text-[var(--color-muted-text)]">
                    <flux:icon name="magnifying-glass" class="h-5 w-5" />
                </div>

                <input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    placeholder="Search modules, routes, descriptions, and apps..."
                    class="h-[4.5rem] w-full rounded-[1.9rem] border border-[var(--color-glass-border)] bg-[var(--color-surface-1)] pl-14 pr-44 text-base font-semibold text-[var(--color-text-main)] shadow-2xl shadow-[var(--color-card-shadow)] outline-none ring-0 backdrop-blur transition duration-300 placeholder:text-[var(--color-muted-text)] focus:border-[var(--color-primary)] focus:shadow-[0_0_0_6px_color-mix(in_srgb,var(--color-primary)_14%,transparent)]"
                >

                <div class="absolute inset-y-0 right-0 flex items-center gap-2 pr-3">
                    @if ($search !== '')
                        <flux:button variant="ghost" size="sm" wire:click="clearSearch">
                            Clear
                        </flux:button>
                    @endif

                    <flux:button variant="primary" size="sm" icon="sparkles" x-on:click="window.dispatchEvent(new CustomEvent('toggle-command-palette'))">
                        Palette
                    </flux:button>
                </div>
            </div>

            <p class="text-xs font-bold uppercase tracking-[0.22em] text-[var(--color-muted-text)]">
                Use the launcher search for instant filtering, or press <span class="text-[var(--color-primary)]">Ctrl/Cmd + K</span> for the full command palette.
            </p>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
        <div class="mb-6 flex items-center justify-between gap-4">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.28em] text-[var(--color-muted-text)]">Modules</p>
                <h2 class="mt-2 text-2xl font-black tracking-[-0.03em] text-[var(--color-text-main)]">Workspace Launcher</h2>
            </div>

            <p class="text-sm text-[var(--color-soft-text)]">
                <span>{{ $visibleModuleCount }}</span>
                <span>results</span>
            </p>
        </div>

        @if ($modules !== [])
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4 xl:grid-cols-6">
            @foreach ($modules as $module)
                <a
                    wire:key="launcher-module-{{ $module['id'] }}"
                    wire:navigate
                    href="{{ $module['route'] ?? '#' }}"
                    class="group relative flex min-h-[19rem] flex-col overflow-hidden rounded-[1.75rem] border border-[var(--color-glass-border)] bg-[var(--color-surface-1)] p-5 shadow-lg shadow-[var(--color-card-shadow)] transition duration-300 hover:-translate-y-1.5 hover:border-[var(--color-primary)]/55 hover:shadow-2xl hover:shadow-[var(--color-card-shadow)]"
                >
                    <div class="absolute inset-x-0 top-0 h-1.5 bg-[linear-gradient(90deg,var(--color-primary),var(--color-accent))] opacity-0 transition duration-300 group-hover:opacity-100"></div>

                    <div class="flex items-start justify-between gap-3">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-[linear-gradient(135deg,var(--color-primary-soft),var(--color-accent-soft))] text-[var(--color-primary)] shadow-inner">
                            <flux:icon :name="$module['icon'] ?: 'squares-2x2'" class="h-7 w-7" />
                        </div>

                        <div class="flex flex-col items-end gap-2">
                            <flux:badge color="zinc" size="sm">
                                <span>{{ $module['badge'] }}</span>
                                <span class="ml-1">Apps</span>
                            </flux:badge>

                            <span
                                class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-black uppercase tracking-[0.18em]"
                                @class([
                                    'bg-ledger-in-subtle text-ledger-in' => $module['is_active'],
                                    'bg-ledger-out-subtle text-ledger-out' => ! $module['is_active'],
                                ])
                            >
                                <span
                                    class="h-2 w-2 rounded-full"
                                    @class([
                                        'bg-ledger-in' => $module['is_active'],
                                        'bg-ledger-out' => ! $module['is_active'],
                                    ])
                                ></span>
                                <span>{{ $module['is_active'] ? 'Enabled' : 'Disabled' }}</span>
                            </span>
                        </div>
                    </div>

                    <div class="mt-5 flex-1 space-y-3">
                        <div>
                            <h3 class="line-clamp-2 text-lg font-black tracking-[-0.02em] text-[var(--color-text-main)]">{{ $module['title'] }}</h3>
                            <p class="mt-1 text-xs font-bold uppercase tracking-[0.22em] text-[var(--color-primary)]/85">{{ $module['category'] ?: 'Module' }}</p>
                        </div>

                        <p class="line-clamp-4 text-sm leading-6 text-[var(--color-soft-text)]">{{ $module['description'] ?: 'Open this workspace to explore its applications and workflows.' }}</p>
                    </div>

                    <div class="mt-5 flex items-center justify-between border-t border-[var(--color-glass-border)] pt-4">
                        <span class="truncate text-xs font-bold uppercase tracking-[0.18em] text-[var(--color-muted-text)]">{{ $module['route_name'] ?: 'No route' }}</span>

                        <div class="flex items-center gap-2 text-[var(--color-primary)] transition duration-300 group-hover:translate-x-1">
                            <span class="text-xs font-black uppercase tracking-[0.18em]">Open</span>
                            <flux:icon name="arrow-right" class="h-4 w-4" />
                        </div>
                    </div>
                </a>
            @endforeach
            </div>
        @else
            <div class="rounded-[2rem] border border-dashed border-[var(--color-glass-border)] bg-[var(--color-surface-1)]/78 px-6 py-16 text-center shadow-lg shadow-[var(--color-card-shadow)]">
            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-[linear-gradient(135deg,var(--color-primary-soft),var(--color-accent-soft))] text-[var(--color-primary)]">
                <flux:icon name="magnifying-glass" class="h-9 w-9" />
            </div>

            <h3 class="mt-6 text-2xl font-black tracking-[-0.03em] text-[var(--color-text-main)]">No matching modules found</h3>
            <p class="mx-auto mt-3 max-w-xl text-sm leading-7 text-[var(--color-soft-text)]">
                Try a broader search term, clear the current filter, or browse another workspace category.
            </p>

            <div class="mt-6 flex justify-center">
                <flux:button variant="primary" wire:click="clearSearch" icon="arrow-path">
                    Reset Search
                </flux:button>
            </div>
            </div>
        @endif
    </section>
</div>
