<div
    x-data="{
        open: false,
        loading: false,
        x: 0,
        y: 0,
        anchor: null,
        data: null,
        lastFocused: null,
        cache: {},
        inflight: {},
        requestId: 0,
        async show(detail) {
            const currentRequest = ++this.requestId;
            this.lastFocused = document.activeElement;
            this.anchor = detail.anchor;
            this.x = detail.x; this.y = detail.y;
            this.open = true;

            const cacheKey = [
                '{{ $contextToken }}',
                detail.code,
                detail.key
            ].join(':');

            if (this.cache[cacheKey]) {
                this.data = this.cache[cacheKey];
                this.loading = false;
                this.reposition();
                this.$nextTick(() => this.$refs.panel?.focus());
                return;
            }

            this.loading = true;
            this.data = null;

            if (! this.inflight[cacheKey]) {
                this.inflight[cacheKey] = $wire.loadPreview(detail.code, detail.key).then((result) => {
                    this.cache[cacheKey] = result;
                    return result;
                }).catch(() => {
                    return { available: false };
                }).finally(() => {
                    delete this.inflight[cacheKey];
                });
            }

            const result = await this.inflight[cacheKey];
            if (currentRequest !== this.requestId || ! this.open) {
                return;
            }

            this.data = result;
            this.loading = false;
            this.reposition();
            this.$nextTick(() => this.$refs.panel?.focus());
        },
        reposition() {
            this.$nextTick(() => {
                const panel = this.$refs.panel;
                if (! panel || ! this.anchor) {
                    return;
                }

                const gap = 8;
                const rect = this.anchor.getBoundingClientRect();
                const panelWidth = panel.offsetWidth || 288;
                const panelHeight = panel.offsetHeight || 120;

                const isRtl = document.documentElement.dir === 'rtl';
                let x = isRtl ? (rect.right - panelWidth) : rect.left;

                const maxX = window.innerWidth - panelWidth - gap;
                x = Math.max(gap, Math.min(x, maxX));

                let y = rect.bottom + gap;
                if (y + panelHeight > window.innerHeight - gap) {
                    y = rect.top - panelHeight - gap;
                }

                const maxY = window.innerHeight - panelHeight - gap;
                y = Math.max(gap, Math.min(y, maxY));

                this.x = x;
                this.y = y;
            });
        },
        close() {
            this.requestId++;
            this.open = false;
            this.data = null;
            this.anchor = null;
            this.loading = false;
            if (this.lastFocused) { this.lastFocused.focus(); this.lastFocused = null; }
        }
    }"
    x-init="
        window.addEventListener('livewire:navigated', () => {
            cache = {};
            inflight = {};
        });
    "
    x-on:record-reference:open-preview.window="show($event.detail)"
    x-on:keydown.escape.window="open && close()"
    x-on:click.outside="open && close()"
    x-on:resize.window="open && reposition()"
    x-on:scroll.window.passive="open && reposition()"
>
    <div
        x-show="open"
        x-cloak
        x-ref="panel"
        tabindex="-1"
        role="dialog"
        aria-label="{{ __('messages.record_reference.preview_label') }}"
        x-transition.opacity.duration.150ms
        x-bind:style="`position: fixed; left: ${x}px; top: ${y}px; z-index: 60;`"
        class="w-72 max-w-[90vw] rounded-lg border border-[var(--color-border,#e5e7eb)] bg-[var(--color-surface,#fff)] p-3 shadow-lg dark:bg-zinc-900"
    >
        <template x-if="loading">
            <div class="animate-pulse space-y-2">
                <div class="h-3 w-2/3 rounded bg-zinc-200 dark:bg-zinc-700"></div>
                <div class="h-2 w-full rounded bg-zinc-200 dark:bg-zinc-700"></div>
                <div class="h-2 w-1/2 rounded bg-zinc-200 dark:bg-zinc-700"></div>
            </div>
        </template>

        <button
            type="button"
            class="absolute end-2 top-2 inline-flex size-7 items-center justify-center rounded-md text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:hover:bg-zinc-800 dark:hover:text-zinc-50"
            aria-label="{{ __('messages.record_reference.close_preview') }}"
            x-on:click="close()"
        >
            <flux:icon icon="x-mark" variant="micro" />
        </button>

        <template x-if="!loading && data && !data.available">
            <p class="pe-8 text-xs text-[var(--color-content-tertiary,#6b7280)]">{{ __('messages.record_reference.preview_unavailable') }}</p>
        </template>

        <template x-if="!loading && data && data.available">
            <div class="space-y-2 pe-8">
                <a x-bind:href="data.url" class="text-sm font-semibold hover:underline" x-text="data.title"></a>
                <dl class="grid grid-cols-2 gap-x-3 gap-y-1 text-xs">
                    <template x-for="(fact, i) in data.facts" x-bind:key="i">
                        <div>
                            <dt class="text-[var(--color-content-tertiary,#6b7280)]" x-text="fact.label"></dt>
                            <dd class="font-medium" x-text="fact.value"></dd>
                        </div>
                    </template>
                </dl>
            </div>
        </template>
    </div>
</div>
