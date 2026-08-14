@php
    $userInitials = \Illuminate\Support\Str::of($userName)->trim()->explode(' ')->map(
        fn (string $part): string => \Illuminate\Support\Str::substr($part, 0, 1)
    )->take(2)->implode('');
@endphp

<header
    class="sticky top-0 z-40 border-b border-[var(--color-border)] bg-[color:var(--color-card-bg)]/92 backdrop-blur-xl"
    style="
        --launcher-surface: color-mix(in srgb, var(--color-card-bg) 88%, white 12%);
        --launcher-muted: color-mix(in srgb, var(--color-text-main) 58%, transparent);
        --launcher-soft: color-mix(in srgb, var(--color-primary) 10%, transparent);
    "
>
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-4 px-4 py-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-4">
            <div class="flex min-w-0 flex-1 items-center gap-3 lg:flex-[1.2]">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[var(--color-primary)] text-sm font-black text-white shadow-lg shadow-[var(--color-primary)]/25">
                    TE
                </div>

                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <flux:brand href="{{ url('/') }}" class="truncate text-[var(--color-text-main)]">
                            {{ __('messages.app_name') }}
                        </flux:brand>
                        <span class="hidden rounded-full bg-[var(--launcher-soft)] px-2 py-1 text-[10px] font-bold uppercase tracking-[0.22em] text-[var(--color-primary)] sm:inline-flex">
                            ERP
                        </span>
                    </div>

                    <p class="truncate text-xs font-medium text-[var(--launcher-muted)]">
                        {{ __('Application Launcher') }}
                    </p>
                </div>
            </div>

            <div class="hidden min-w-0 flex-1 md:block">
                <flux:input
                    id="launcher-search"
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    icon="magnifying-glass"
                    placeholder="Search modules and apps..."
                    class="w-full rounded-2xl border-[var(--color-border)] bg-[var(--launcher-surface)] text-[var(--color-text-main)] placeholder:text-[var(--launcher-muted)]"
                />
            </div>

            <div class="flex shrink-0 items-center gap-3 lg:flex-1 lg:justify-end">
                <flux:button
                    type="button"
                    variant="ghost"
                    icon="bell"
                    class="rounded-2xl border border-[var(--color-border)] bg-[var(--launcher-surface)] text-[var(--color-text-main)] hover:border-[var(--color-primary)] hover:text-[var(--color-primary)]"
                    aria-label="Notifications"
                />

                <flux:separator vertical class="hidden h-10 sm:block" />

                <div class="hidden items-center gap-3 rounded-2xl border border-[var(--color-border)] bg-[var(--launcher-surface)] px-3 py-2 sm:flex">
                    <flux:avatar
                        size="sm"
                        circle
                        class="bg-[var(--launcher-soft)] text-[var(--color-primary)]"
                    >
                        {{ $userInitials !== '' ? $userInitials : 'U' }}
                    </flux:avatar>

                    <div class="min-w-0 text-right">
                        <p class="truncate text-sm font-bold text-[var(--color-text-main)]">
                            {{ $userName }}
                        </p>
                        <p class="truncate text-xs font-medium uppercase tracking-[0.16em] text-[var(--launcher-muted)]">
                            {{ $userRole }}
                        </p>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <flux:button
                        type="submit"
                        variant="ghost"
                        icon="arrow-left-start-on-rectangle"
                        class="rounded-2xl border border-[var(--color-border)] bg-[var(--launcher-surface)] text-[var(--color-text-main)] hover:border-rose-400 hover:text-rose-600"
                    >
                        <span class="hidden sm:inline">{{ __('messages.logout') }}</span>
                    </flux:button>
                </form>
            </div>
        </div>

        <div class="md:hidden">
            <flux:input
                id="launcher-search-mobile"
                type="search"
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Search modules and apps..."
                class="w-full rounded-2xl border-[var(--color-border)] bg-[var(--launcher-surface)] text-[var(--color-text-main)] placeholder:text-[var(--launcher-muted)]"
            />
        </div>
    </div>
</header>
