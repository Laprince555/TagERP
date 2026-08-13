@props([
    'isOpen' => false,
])

<div
    {{ $attributes->merge(['class' => 'relative']) }}
    x-data="{
        isOpen: @js($isOpen),
        query: '',
        selectedModuleIndex: 0,
        selectedSubModuleIndex: 0,
        selectedApplicationIndex: 0,
        navigationTree: @js($globalNavigationTree ?? []),
        label(item) {
            if (! item) {
                return '';
            }

            if (typeof item.name === 'string') {
                return item.name;
            }

            return item.name?.{{ app()->getLocale() === 'ar' ? "'ar'" : "'en'" }} ?? Object.values(item.name ?? {})[0] ?? item.code ?? '';
        },
        modules() {
            return this.navigationTree.filter((module) => {
                const moduleLabel = this.label(module).toLowerCase();
                const search = this.query.toLowerCase().trim();

                if (! search) {
                    return true;
                }

                if (moduleLabel.includes(search)) {
                    return true;
                }

                return (module.sub_modules ?? []).some((subModule) => {
                    if (this.label(subModule).toLowerCase().includes(search)) {
                        return true;
                    }

                    return (subModule.applications ?? []).some((application) => this.label(application).toLowerCase().includes(search));
                });
            });
        },
        currentModule() {
            return this.modules()[this.selectedModuleIndex] ?? null;
        },
        subModules() {
            const subModules = this.currentModule()?.sub_modules ?? [];
            const search = this.query.toLowerCase().trim();

            if (! search) {
                return subModules;
            }

            return subModules.filter((subModule) => {
                if (this.label(subModule).toLowerCase().includes(search)) {
                    return true;
                }

                return (subModule.applications ?? []).some((application) => this.label(application).toLowerCase().includes(search));
            });
        },
        currentSubModule() {
            return this.subModules()[this.selectedSubModuleIndex] ?? null;
        },
        applications() {
            const search = this.query.toLowerCase().trim();
            const currentSubModule = this.currentSubModule();

            if (currentSubModule) {
                const subModuleApplications = currentSubModule.applications ?? [];

                if (! search) {
                    return subModuleApplications;
                }

                return subModuleApplications.filter((application) => this.label(application).toLowerCase().includes(search));
            }

            const moduleApplications = (this.currentModule()?.sub_modules ?? []).flatMap((subModule) => subModule.applications ?? []);

            if (! search) {
                return moduleApplications;
            }

            return moduleApplications.filter((application) => this.label(application).toLowerCase().includes(search));
        },
        selectModule(index) {
            this.selectedModuleIndex = index;
            this.selectedSubModuleIndex = 0;
            this.selectedApplicationIndex = 0;
        },
        selectSubModule(index) {
            this.selectedSubModuleIndex = index;
            this.selectedApplicationIndex = 0;
        },
        selectApplication(index) {
            this.selectedApplicationIndex = index;
        }
    }"
    x-cloak
    @keydown.escape.window="isOpen = false"
>
    <div
        x-show="isOpen"
        x-transition.opacity
        class="fixed inset-0 z-40 bg-slate-950/45 backdrop-blur-sm"
        @click="isOpen = false"
    ></div>

    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="translate-y-4 opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="translate-y-4 opacity-0"
        class="fixed inset-x-4 top-6 z-50 mx-auto w-full max-w-6xl overflow-hidden rounded-[2rem] border border-[var(--color-border)] bg-[var(--color-card-bg)] shadow-2xl shadow-slate-950/20"
        @click.outside="isOpen = false"
    >
        <div class="flex items-center gap-3 border-b border-[var(--color-border)] px-5 py-4 sm:px-6">
            <div class="relative flex-1">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-[var(--color-text-main)]/45">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 3.471 9.766l2.631 2.632a.75.75 0 1 0 1.06-1.06l-2.632-2.631A5.5 5.5 0 0 0 9 3.5ZM5 9a4 4 0 1 1 8 0a4 4 0 0 1-8 0Z" clip-rule="evenodd" />
                    </svg>
                </span>

                <input
                    type="search"
                    x-model="query"
                    placeholder="Search modules, sub-modules, and applications..."
                    class="w-full rounded-2xl border border-[var(--color-border)] bg-[var(--color-canvas-bg)] py-3 pl-11 pr-4 text-sm text-[var(--color-text-main)] placeholder:text-[var(--color-text-main)]/45 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                >
            </div>

            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-2xl border border-[var(--color-border)] bg-[var(--color-canvas-bg)] px-4 py-3 text-xs font-bold uppercase tracking-[0.18em] text-[var(--color-text-main)]/70 transition hover:border-primary hover:text-primary"
                @click="isOpen = false"
            >
                <span>ESC</span>
                <span class="hidden text-[var(--color-text-main)]/45 sm:inline">Close</span>
            </button>
        </div>

        <div class="grid min-h-[28rem] grid-cols-1 divide-y divide-[var(--color-border)] lg:grid-cols-3 lg:divide-x lg:divide-y-0">
            <section class="flex min-h-0 flex-col">
                <div class="border-b border-[var(--color-border)] px-5 py-3 text-xs font-black uppercase tracking-[0.2em] text-[var(--color-text-main)]/45 sm:px-6">
                    Modules
                </div>

                <div class="flex-1 overflow-y-auto px-3 py-3 sm:px-4">
                    <template x-for="(module, moduleIndex) in modules()" :key="module.code ?? moduleIndex">
                        <button
                            type="button"
                            class="mb-2 flex w-full items-center justify-between rounded-2xl border-l-2 border-transparent px-4 py-3 text-left text-sm font-semibold text-[var(--color-text-main)] transition hover:border-primary/40 hover:bg-[var(--color-primary)]/5 hover:text-primary"
                            :class="selectedModuleIndex === moduleIndex ? 'border-primary bg-primary/10 text-primary' : ''"
                            @click="selectModule(moduleIndex)"
                        >
                            <span class="truncate" x-text="label(module)"></span>
                            <span class="ml-3 shrink-0 rounded-full bg-[var(--color-canvas-bg)] px-2 py-1 text-[11px] font-bold text-[var(--color-text-main)]/50" x-text="(module.sub_modules ?? []).length"></span>
                        </button>
                    </template>
                </div>
            </section>

            <section class="flex min-h-0 flex-col">
                <div class="border-b border-[var(--color-border)] px-5 py-3 text-xs font-black uppercase tracking-[0.2em] text-[var(--color-text-main)]/45 sm:px-6">
                    SubModules
                </div>

                <div class="flex-1 overflow-y-auto px-3 py-3 sm:px-4">
                    <template x-if="subModules().length">
                        <div>
                            <template x-for="(subModule, subModuleIndex) in subModules()" :key="subModule.code ?? subModuleIndex">
                                <button
                                    type="button"
                                    class="mb-2 flex w-full items-center justify-between rounded-2xl border-l-2 border-transparent px-4 py-3 text-left text-sm font-semibold text-[var(--color-text-main)] transition hover:border-primary/40 hover:bg-[var(--color-primary)]/5 hover:text-primary"
                                    :class="selectedSubModuleIndex === subModuleIndex ? 'border-primary bg-primary/10 text-primary' : ''"
                                    @click="selectSubModule(subModuleIndex)"
                                >
                                    <span class="truncate" x-text="label(subModule)"></span>
                                    <span class="ml-3 shrink-0 rounded-full bg-[var(--color-canvas-bg)] px-2 py-1 text-[11px] font-bold text-[var(--color-text-main)]/50" x-text="(subModule.applications ?? []).length"></span>
                                </button>
                            </template>
                        </div>
                    </template>

                    <template x-if="! subModules().length">
                        <div class="flex h-full min-h-48 items-center justify-center rounded-3xl border border-dashed border-[var(--color-border)] bg-[var(--color-canvas-bg)]/50 px-6 text-center text-sm text-[var(--color-text-main)]/55">
                            Select a module with sub-modules to continue.
                        </div>
                    </template>
                </div>
            </section>

            <section class="flex min-h-0 flex-col">
                <div class="border-b border-[var(--color-border)] px-5 py-3 text-xs font-black uppercase tracking-[0.2em] text-[var(--color-text-main)]/45 sm:px-6">
                    Applications
                </div>

                <div class="flex-1 overflow-y-auto px-3 py-3 sm:px-4">
                    <template x-if="applications().length">
                        <div>
                            <template x-for="(application, applicationIndex) in applications()" :key="application.code ?? applicationIndex">
                                <a
                                    class="mb-2 flex items-center justify-between rounded-2xl border-l-2 border-transparent px-4 py-3 text-sm font-semibold text-[var(--color-text-main)] transition hover:border-primary/40 hover:bg-[var(--color-primary)]/5 hover:text-primary"
                                    :class="selectedApplicationIndex === applicationIndex ? 'border-primary bg-primary/10 text-primary' : ''"
                                    :href="application.route ?? '#'"
                                    @click="selectApplication(applicationIndex)"
                                >
                                    <div class="min-w-0">
                                        <p class="truncate" x-text="label(application)"></p>
                                        <p class="mt-1 truncate text-xs font-medium text-[var(--color-text-main)]/45" x-text="application.code ?? ''"></p>
                                    </div>

                                    <svg class="ml-3 h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M11.22 4.47a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 1 1-1.06-1.06l2.97-2.97H4.75a.75.75 0 0 1 0-1.5h9.44l-2.97-2.97a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                            </template>
                        </div>
                    </template>

                    <template x-if="! applications().length">
                        <div class="flex h-full min-h-48 items-center justify-center rounded-3xl border border-dashed border-[var(--color-border)] bg-[var(--color-canvas-bg)]/50 px-6 text-center text-sm text-[var(--color-text-main)]/55">
                            No applications match the current selection.
                        </div>
                    </template>
                </div>
            </section>
        </div>

        <footer class="flex flex-wrap items-center gap-2 border-t border-[var(--color-border)] px-5 py-4 text-xs font-medium text-[var(--color-text-main)]/55 sm:px-6">
            <span class="rounded-full border border-[var(--color-border)] bg-[var(--color-canvas-bg)] px-3 py-1.5">↑↓ Navigate</span>
            <span class="rounded-full border border-[var(--color-border)] bg-[var(--color-canvas-bg)] px-3 py-1.5">Enter Open</span>
            <span class="rounded-full border border-[var(--color-border)] bg-[var(--color-canvas-bg)] px-3 py-1.5">ESC Close</span>
            <span class="rounded-full border border-[var(--color-border)] bg-[var(--color-canvas-bg)] px-3 py-1.5">Click Select</span>
        </footer>
    </div>
</div>
