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
    <flux:modal name="command-palette" class="w-full max-w-6xl">
        <div class="space-y-5">
            <div class="flex items-start justify-between gap-4 border-b border-[var(--color-border)] pb-4">
                <div>
                    <flux:heading size="lg">Command Palette</flux:heading>
                    <flux:text class="mt-1 text-sm text-[var(--color-text-main)]/65">
                        Jump across modules, submodules, and applications from one place.
                    </flux:text>
                </div>

                <flux:button variant="ghost" x-on:click="close()" icon="x-mark">
                    Close
                </flux:button>
            </div>

            <flux:field>
                <flux:label>Search</flux:label>
                <flux:input
                    x-model="search"
                    x-ref="searchInput"
                    type="search"
                    placeholder="Type to filter navigation..."
                    autocomplete="off"
                />
            </flux:field>

            <div class="grid gap-4 lg:grid-cols-3">
                <section class="rounded-2xl border border-[var(--color-border)] bg-[var(--color-bg)]/70 p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <flux:heading size="sm">Modules</flux:heading>
                        <flux:badge color="zinc" inset x-text="filteredTree.length"></flux:badge>
                    </div>

                    <div class="space-y-2">
                        <template x-for="(module, moduleIndex) in filteredTree" :key="module.code ?? module.id ?? moduleIndex">
                            <button
                                type="button"
                                class="flex w-full items-start gap-3 rounded-2xl border px-3 py-3 text-left transition"
                                :class="activeColumn === 0 && activeModuleIndex === moduleIndex
                                    ? 'border-[var(--color-primary)] bg-[var(--color-primary)]/10 text-[var(--color-text-main)]'
                                    : 'border-transparent bg-transparent text-[var(--color-text-main)]/80 hover:border-[var(--color-border)] hover:bg-[var(--color-card-bg)]'"
                                x-on:click="selectModule(moduleIndex)"
                            >
                                <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[var(--color-primary)]/12 text-xs font-black text-[var(--color-primary)]">
                                    <span x-text="initials(module.name)"></span>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-bold" x-text="module.name"></p>
                                    <p class="mt-1 line-clamp-2 text-xs text-[var(--color-text-main)]/55" x-text="module.description || 'No description'"></p>
                                </div>
                            </button>
                        </template>
                    </div>
                </section>

                <section class="rounded-2xl border border-[var(--color-border)] bg-[var(--color-bg)]/70 p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <flux:heading size="sm">SubModules</flux:heading>
                        <flux:badge color="zinc" inset x-text="activeSubmodules.length"></flux:badge>
                    </div>

                    <div class="space-y-2">
                        <template x-if="activeSubmodules.length === 0">
                            <div class="rounded-2xl border border-dashed border-[var(--color-border)] px-4 py-6 text-center text-sm text-[var(--color-text-main)]/55">
                                Select a module to explore its submodules.
                            </div>
                        </template>

                        <template x-for="(submodule, submoduleIndex) in activeSubmodules" :key="submodule.code ?? submodule.id ?? submoduleIndex">
                            <button
                                type="button"
                                class="flex w-full items-start gap-3 rounded-2xl border px-3 py-3 text-left transition"
                                :class="activeColumn === 1 && activeSubmoduleIndex === submoduleIndex
                                    ? 'border-[var(--color-primary)] bg-[var(--color-primary)]/10 text-[var(--color-text-main)]'
                                    : 'border-transparent bg-transparent text-[var(--color-text-main)]/80 hover:border-[var(--color-border)] hover:bg-[var(--color-card-bg)]'"
                                x-on:click="selectSubmodule(submoduleIndex)"
                            >
                                <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[var(--color-primary)]/12 text-xs font-black text-[var(--color-primary)]">
                                    <span x-text="initials(submodule.name)"></span>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-bold" x-text="submodule.name"></p>
                                    <p class="mt-1 line-clamp-2 text-xs text-[var(--color-text-main)]/55" x-text="submodule.description || 'No description'"></p>
                                </div>
                            </button>
                        </template>
                    </div>
                </section>

                <section class="rounded-2xl border border-[var(--color-border)] bg-[var(--color-bg)]/70 p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <flux:heading size="sm">Applications</flux:heading>
                        <flux:badge color="zinc" inset x-text="activeApplications.length"></flux:badge>
                    </div>

                    <div class="space-y-2">
                        <template x-if="activeApplications.length === 0">
                            <div class="rounded-2xl border border-dashed border-[var(--color-border)] px-4 py-6 text-center text-sm text-[var(--color-text-main)]/55">
                                Select a submodule to see available applications.
                            </div>
                        </template>

                        <template x-for="(application, applicationIndex) in activeApplications" :key="application.code ?? application.id ?? applicationIndex">
                            <button
                                type="button"
                                class="flex w-full items-start gap-3 rounded-2xl border px-3 py-3 text-left transition"
                                :class="activeColumn === 2 && activeAppIndex === applicationIndex
                                    ? 'border-[var(--color-primary)] bg-[var(--color-primary)]/10 text-[var(--color-text-main)]'
                                    : 'border-transparent bg-transparent text-[var(--color-text-main)]/80 hover:border-[var(--color-border)] hover:bg-[var(--color-card-bg)]'"
                                x-on:click="activeColumn = 2; activeAppIndex = applicationIndex"
                            >
                                <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-500/12 text-xs font-black text-emerald-600">
                                    <span x-text="initials(application.name)"></span>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="truncate text-sm font-bold" x-text="application.name"></p>
                                        <span
                                            x-show="currentRoute && application.route === currentRoute"
                                            class="rounded-full bg-emerald-500/12 px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-emerald-600"
                                        >
                                            Current
                                        </span>
                                    </div>
                                    <p class="mt-1 line-clamp-2 text-xs text-[var(--color-text-main)]/55" x-text="application.description || application.route || 'No description'"></p>
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
                        this.isOpen = !this.isOpen;

                        if (this.isOpen) {
                            this.$nextTick(() => this.$refs.searchInput?.focus());
                        }
                    },
                    close() {
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
                            window.location.href = application.route;
                        }
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
                    initials(value) {
                        return String(value ?? '')
                            .trim()
                            .split(/\s+/)
                            .slice(0, 2)
                            .map((segment) => segment.charAt(0))
                            .join('')
                            .toUpperCase() || 'NA';
                    },
                }));
            });
        </script>
    @endpush
@endonce
