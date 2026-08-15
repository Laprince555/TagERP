@php
    $paletteIcons = collect($tree)
        ->flatMap(function (array $module): array {
            return array_filter([
                $module['icon'] ?? null,
                ...collect($module['sub_modules'] ?? [])
                    ->flatMap(function (array $subModule): array {
                        return array_filter([
                            $subModule['icon'] ?? null,
                            ...collect($subModule['applications'] ?? [])->pluck('icon')->filter()->all(),
                        ]);
                    })
                    ->all(),
            ]);
        })
        ->filter()
        ->push('squares-2x2')
        ->unique()
        ->values();
@endphp

<div
    x-data="commandPalette({
        tree: @js($tree),
        currentRoute: @js($currentRoute),
    })"
    x-on:keydown.window.prevent.arrow-down="navigateVertical(1)"
    x-on:keydown.window.prevent.arrow-up="navigateVertical(-1)"
    x-on:keydown.window.prevent.arrow-right="navigateHorizontal(1)"
    x-on:keydown.window.prevent.arrow-left="navigateHorizontal(-1)"
    x-on:keydown.window.prevent.enter="executeSelection()"
    x-on:keydown.window.prevent.escape="close()"
    x-on:toggle-command-palette.window="toggle()"
>
    <style>
        :root {
            --palette-shell: color-mix(in srgb, var(--color-card-bg) 90%, var(--color-canvas-bg) 10%);
            --palette-panel: color-mix(in srgb, var(--color-card-bg) 94%, var(--color-canvas-bg) 6%);
            --palette-panel-strong: color-mix(in srgb, var(--color-card-bg) 82%, var(--color-sidebar-bg) 18%);
            --palette-border: color-mix(in srgb, var(--color-border) 84%, var(--color-text-main) 16%);
            --palette-soft-text: color-mix(in srgb, var(--color-text-main) 86%, transparent);
            --palette-muted-text: color-mix(in srgb, var(--color-text-main) 72%, transparent);
            --palette-active: color-mix(in srgb, var(--color-primary) 18%, var(--color-card-bg) 82%);
            --palette-active-border: color-mix(in srgb, var(--color-primary) 70%, var(--color-border) 30%);
            --palette-icon-bg: color-mix(in srgb, var(--color-primary) 12%, transparent);
            --palette-app-icon-bg: color-mix(in srgb, var(--color-primary) 18%, transparent);
            --palette-badge-bg: color-mix(in srgb, var(--color-card-bg) 74%, var(--color-canvas-bg) 26%);
        }

        dialog[data-modal='command-palette'] {
            background-color: var(--palette-shell) !important;
            color: var(--color-text-main) !important;
            border-color: var(--palette-border) !important;
            box-shadow:
                0 28px 80px color-mix(in srgb, var(--color-canvas-bg) 68%, transparent),
                0 0 0 1px color-mix(in srgb, var(--palette-border) 75%, transparent) !important;
        }

        dialog[data-modal='command-palette']::backdrop {
            background:
                color-mix(in srgb, var(--color-canvas-bg) 68%, transparent);
            backdrop-filter: blur(14px);
        }

        dialog[data-modal='command-palette'] [data-flux-modal-close] [data-flux-button] {
            color: var(--palette-muted-text) !important;
        }

        dialog[data-modal='command-palette'] [data-flux-modal-close] [data-flux-button]:hover {
            color: var(--color-text-main) !important;
            background-color: var(--palette-panel-strong) !important;
        }

        dialog[data-modal='command-palette'] [data-flux-heading],
        dialog[data-modal='command-palette'] [data-flux-label] {
            color: var(--color-text-main) !important;
        }
    </style>

    <flux:modal
        name="command-palette"
        x-on:close="isOpen = false"
        class="w-full max-w-6xl !border-[var(--palette-border)] !bg-[var(--palette-shell)] !text-[var(--color-text-main)]"
        style="background-color: var(--palette-shell); color: var(--color-text-main); border-color: var(--palette-border);"
    >
        <div class="space-y-5 rounded-[1.8rem] bg-[var(--palette-shell)] p-1">
            <div class="border-b border-[var(--palette-border)] pb-4">
                <div>
                    <flux:heading size="lg" class="text-[var(--color-text-main)]">Command Palette</flux:heading>
                    <flux:text class="mt-1 text-sm text-[var(--palette-soft-text)]">
                        Jump across modules, submodules, and applications from one place.
                    </flux:text>
                </div>
            </div>

            <flux:field>
                <flux:label class="text-[var(--color-text-main)]">Search</flux:label>
                <flux:input
                    x-model="search"
                    x-ref="searchInput"
                    type="search"
                    placeholder="Type to filter navigation..."
                    autocomplete="off"
                    class="border-[var(--palette-border)] bg-[var(--palette-panel)] text-[var(--color-text-main)] placeholder:text-[var(--palette-muted-text)]"
                />
            </flux:field>

            <div class="grid gap-4 lg:grid-cols-3">
                <section class="rounded-2xl border border-[var(--palette-border)] bg-[var(--palette-panel)] p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <flux:heading size="sm" class="text-[var(--color-text-main)]">Modules</flux:heading>
                        <span class="inline-flex min-w-8 items-center justify-center rounded-xl bg-[var(--palette-badge-bg)] px-2 py-1 text-xs font-bold text-[var(--palette-soft-text)]" x-text="filteredTree.length"></span>
                    </div>

                    <div class="space-y-2">
                        <template x-for="(module, moduleIndex) in filteredTree" :key="module.code ?? module.id ?? moduleIndex">
                            <div
                                class="flex items-start gap-2 rounded-2xl border p-2 transition"
                                :class="activeColumn === 0 && activeModuleIndex === moduleIndex
                                    ? 'border-[var(--palette-active-border)] bg-[var(--palette-active)] text-[var(--color-text-main)]'
                                    : 'border-transparent bg-transparent text-[var(--palette-soft-text)] hover:border-[var(--palette-border)] hover:bg-[var(--palette-panel-strong)]'"
                            >
                                <button
                                    type="button"
                                    class="flex min-w-0 flex-1 items-start gap-3 rounded-xl px-1 py-1 text-left"
                                    x-on:click="selectModule(moduleIndex)"
                                >
                                    <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[var(--palette-icon-bg)] text-[var(--color-primary)]">
                                        @foreach ($paletteIcons as $icon)
                                            <span :class="(module.icon || 'squares-2x2') === '{{ $icon }}' ? 'block' : 'hidden'">
                                                <flux:icon
                                                    :name="$icon"
                                                    class="h-5 w-5"
                                                />
                                            </span>
                                        @endforeach
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-bold" x-text="module.name"></p>
                                        <p class="mt-1 line-clamp-2 text-xs text-[var(--palette-muted-text)]" x-text="module.description || 'No description'"></p>
                                    </div>
                                </button>

                                <button
                                    type="button"
                                    class="mt-1 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-[var(--palette-border)] bg-[var(--palette-panel)] text-[var(--palette-muted-text)] transition hover:border-[var(--color-primary)] hover:text-[var(--color-primary)]"
                                    x-show="Boolean(module.route)"
                                    x-on:click.stop="navigateToRoute(module.route)"
                                    aria-label="Go to module page"
                                >
                                    <flux:icon name="arrow-top-right-on-square" class="h-4 w-4" />
                                </button>
                            </div>
                        </template>
                    </div>
                </section>

                <section class="rounded-2xl border border-[var(--palette-border)] bg-[var(--palette-panel)] p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <flux:heading size="sm" class="text-[var(--color-text-main)]">SubModules</flux:heading>
                        <span class="inline-flex min-w-8 items-center justify-center rounded-xl bg-[var(--palette-badge-bg)] px-2 py-1 text-xs font-bold text-[var(--palette-soft-text)]" x-text="activeSubmodules.length"></span>
                    </div>

                    <div class="space-y-2">
                        <template x-if="activeSubmodules.length === 0">
                            <div class="rounded-2xl border border-dashed border-[var(--palette-border)] px-4 py-6 text-center text-sm text-[var(--palette-muted-text)]">
                                Select a module to explore its submodules.
                            </div>
                        </template>

                        <template x-for="(submodule, submoduleIndex) in activeSubmodules" :key="submodule.code ?? submodule.id ?? submoduleIndex">
                            <div
                                class="flex items-start gap-2 rounded-2xl border p-2 transition"
                                :class="activeColumn === 1 && activeSubmoduleIndex === submoduleIndex
                                    ? 'border-[var(--palette-active-border)] bg-[var(--palette-active)] text-[var(--color-text-main)]'
                                    : 'border-transparent bg-transparent text-[var(--palette-soft-text)] hover:border-[var(--palette-border)] hover:bg-[var(--palette-panel-strong)]'"
                            >
                                <button
                                    type="button"
                                    class="flex min-w-0 flex-1 items-start gap-3 rounded-xl px-1 py-1 text-left"
                                    x-on:click="selectSubmodule(submoduleIndex)"
                                >
                                    <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[var(--palette-icon-bg)] text-[var(--color-primary)]">
                                        @foreach ($paletteIcons as $icon)
                                            <span :class="(submodule.icon || 'squares-2x2') === '{{ $icon }}' ? 'block' : 'hidden'">
                                                <flux:icon
                                                    :name="$icon"
                                                    class="h-5 w-5"
                                                />
                                            </span>
                                        @endforeach
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-bold" x-text="submodule.name"></p>
                                        <p class="mt-1 line-clamp-2 text-xs text-[var(--palette-muted-text)]" x-text="submodule.description || 'No description'"></p>
                                    </div>
                                </button>

                                <button
                                    type="button"
                                    class="mt-1 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-[var(--palette-border)] bg-[var(--palette-panel)] text-[var(--palette-muted-text)] transition hover:border-[var(--color-primary)] hover:text-[var(--color-primary)]"
                                    x-show="Boolean(submodule.route)"
                                    x-on:click.stop="navigateToRoute(submodule.route)"
                                    aria-label="Go to submodule page"
                                >
                                    <flux:icon name="arrow-top-right-on-square" class="h-4 w-4" />
                                </button>
                            </div>
                        </template>
                    </div>
                </section>

                <section class="rounded-2xl border border-[var(--palette-border)] bg-[var(--palette-panel)] p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <flux:heading size="sm" class="text-[var(--color-text-main)]">Applications</flux:heading>
                        <span class="inline-flex min-w-8 items-center justify-center rounded-xl bg-[var(--palette-badge-bg)] px-2 py-1 text-xs font-bold text-[var(--palette-soft-text)]" x-text="activeApplications.length"></span>
                    </div>

                    <div class="space-y-2">
                        <template x-if="activeApplications.length === 0">
                            <div class="rounded-2xl border border-dashed border-[var(--palette-border)] px-4 py-6 text-center text-sm text-[var(--palette-muted-text)]">
                                Select a submodule to see available applications.
                            </div>
                        </template>

                        <template x-for="(application, applicationIndex) in activeApplications" :key="application.code ?? application.id ?? applicationIndex">
                            <button
                                type="button"
                                class="flex w-full items-start gap-3 rounded-2xl border px-3 py-3 text-left transition"
                                :class="activeColumn === 2 && activeAppIndex === applicationIndex
                                    ? 'border-[var(--palette-active-border)] bg-[var(--palette-active)] text-[var(--color-text-main)]'
                                    : 'border-transparent bg-transparent text-[var(--palette-soft-text)] hover:border-[var(--palette-border)] hover:bg-[var(--palette-panel-strong)]'"
                                x-on:click="activeColumn = 2; activeAppIndex = applicationIndex; navigateToRoute(application.route)"
                            >
                                <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[var(--palette-app-icon-bg)] text-[var(--color-primary)]">
                                    @foreach ($paletteIcons as $icon)
                                        <span :class="(application.icon || 'squares-2x2') === '{{ $icon }}' ? 'block' : 'hidden'">
                                            <flux:icon
                                                :name="$icon"
                                                class="h-5 w-5"
                                            />
                                        </span>
                                    @endforeach
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="truncate text-sm font-bold" x-text="application.name"></p>
                                        <span
                                            x-show="currentRoute && application.route_name === currentRoute"
                                            class="rounded-full bg-[var(--palette-app-icon-bg)] px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-[var(--color-primary)]"
                                        >
                                            Current
                                        </span>
                                    </div>
                                    <p class="mt-1 line-clamp-2 text-xs text-[var(--palette-muted-text)]" x-text="application.description || application.route || 'No description'"></p>
                                </div>
                            </button>
                        </template>
                    </div>
                </section>
            </div>
        </div>
    </flux:modal>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('commandPalette', ({ tree = [], currentRoute = null }) => ({
                    tree,
                    currentRoute,
                    search: '',
                    activeModuleIndex: 0,
                    activeSubmoduleIndex: 0,
                    activeAppIndex: 0,
                    activeColumn: 0,
                    isOpen: false,
                    get filteredTree() {
                        const term = this.search.trim().toLowerCase();

                        if (term === '') {
                            return this.tree;
                        }

                        return this.tree
                            .map((module) => {
                                const subModules = (module.sub_modules ?? [])
                                    .map((submodule) => {
                                        const applications = (submodule.applications ?? []).filter((application) => {
                                            return this.matches(application.name, term)
                                                || this.matches(application.description, term)
                                                || this.matches(application.route, term);
                                        });

                                        if (this.matches(submodule.name, term) || this.matches(submodule.description, term) || applications.length > 0) {
                                            return {
                                                ...submodule,
                                                applications,
                                            };
                                        }

                                        return null;
                                    })
                                    .filter(Boolean);

                                if (this.matches(module.name, term) || this.matches(module.description, term) || subModules.length > 0) {
                                    return {
                                        ...module,
                                        sub_modules: subModules,
                                    };
                                }

                                return null;
                            })
                            .filter(Boolean);
                    },
                    get activeModule() {
                        return this.filteredTree[this.activeModuleIndex] ?? null;
                    },
                    get activeSubmodules() {
                        return this.activeModule?.sub_modules ?? [];
                    },
                    get activeSubmodule() {
                        return this.activeSubmodules[this.activeSubmoduleIndex] ?? null;
                    },
                    get activeApplications() {
                        return this.activeSubmodule?.applications ?? [];
                    },
                    toggle() {
                        if (this.isOpen) {
                            this.close();

                            return;
                        }

                        this.isOpen = true;
                        this.$flux.modal('command-palette').show();
                        this.$nextTick(() => this.$refs.searchInput?.focus());
                    },
                    close() {
                        this.$flux.modal('command-palette').close();
                        this.isOpen = false;
                    },
                    selectModule(index) {
                        this.activeModuleIndex = index;
                        this.activeSubmoduleIndex = 0;
                        this.activeAppIndex = 0;
                        this.activeColumn = 1;
                    },
                    selectSubmodule(index) {
                        this.activeSubmoduleIndex = index;
                        this.activeAppIndex = 0;
                        this.activeColumn = 2;
                    },
                    navigateVertical(direction) {
                        if (! this.isOpen) {
                            return;
                        }

                        if (this.activeColumn === 0) {
                            this.activeModuleIndex = this.wrapIndex(this.activeModuleIndex + direction, this.filteredTree.length);
                            this.activeSubmoduleIndex = 0;
                            this.activeAppIndex = 0;
                            return;
                        }

                        if (this.activeColumn === 1) {
                            this.activeSubmoduleIndex = this.wrapIndex(this.activeSubmoduleIndex + direction, this.activeSubmodules.length);
                            this.activeAppIndex = 0;
                            return;
                        }

                        this.activeAppIndex = this.wrapIndex(this.activeAppIndex + direction, this.activeApplications.length);
                    },
                    navigateHorizontal(direction) {
                        if (! this.isOpen) {
                            return;
                        }

                        const nextColumn = Math.min(2, Math.max(0, this.activeColumn + direction));
                        this.activeColumn = nextColumn;

                        if (nextColumn === 1 && this.activeSubmodules.length === 0) {
                            this.activeColumn = 0;
                        }

                        if (nextColumn === 2 && this.activeApplications.length === 0) {
                            this.activeColumn = this.activeSubmodules.length > 0 ? 1 : 0;
                        }
                    },
                    executeSelection() {
                        if (! this.isOpen) {
                            return;
                        }

                        if (this.activeColumn === 0 && this.activeSubmodules.length > 0) {
                            this.activeColumn = 1;
                            return;
                        }

                        if (this.activeColumn === 1 && this.activeApplications.length > 0) {
                            this.activeColumn = 2;
                            return;
                        }

                        const application = this.activeApplications[this.activeAppIndex] ?? null;

                        if (application?.route) {
                            this.navigateToRoute(application.route);
                        }
                    },
                    navigateToRoute(route) {
                        if (! route) {
                            return;
                        }

                        window.location.href = route;
                    },
                    matches(value, term) {
                        return String(value ?? '').toLowerCase().includes(term);
                    },
                    wrapIndex(index, length) {
                        if (length <= 0) {
                            return 0;
                        }

                        if (index < 0) {
                            return length - 1;
                        }

                        if (index >= length) {
                            return 0;
                        }

                        return index;
                    },
                }));
            });
        </script>
    @endpush
@endonce
