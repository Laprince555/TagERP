<nav
    role="navigation"
    aria-label="Breadcrumb"
    class="rounded-2xl border border-[var(--color-border)] bg-[var(--color-card-bg)]/90 px-4 py-3 backdrop-blur"
>
    <ol class="flex items-center gap-1 overflow-x-auto text-sm">
        <li class="flex items-center whitespace-nowrap">
            <a
                href="{{ url('/') }}"
                class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 font-medium text-[var(--color-text-main)]/75 transition hover:bg-[var(--color-bg)] hover:text-[var(--color-primary)]"
            >
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M9.69 2.27a1 1 0 0 1 1.62 0l6.5 8.13a1 1 0 0 1-.78 1.6H15.5v4.25a1.25 1.25 0 0 1-1.25 1.25h-2.5V13a1 1 0 0 0-1-1h-1.5a1 1 0 0 0-1 1v4.5h-2.5A1.25 1.25 0 0 1 4.5 16.25V12H2.97a1 1 0 0 1-.78-1.6l6.5-8.13Z" />
                </svg>
                <span>Home</span>
            </a>
        </li>

        @foreach ($breadcrumbs as $breadcrumb)
            <li wire:key="breadcrumb-{{ $loop->index }}" class="flex items-center whitespace-nowrap">
                <span class="mx-1 text-[var(--color-text-main)]/35">/</span>

                @if ($loop->last)
                    <span class="inline-flex items-center gap-2 rounded-full bg-[var(--color-primary)]/12 px-3 py-1.5 font-semibold text-[var(--color-primary)]">
                        @if (! empty($breadcrumb['icon']))
                            <span class="text-xs leading-none">{{ $breadcrumb['icon'] }}</span>
                        @endif

                        <span>{{ $breadcrumb['label'] }}</span>
                    </span>
                @elseif (! empty($breadcrumb['route']) && Route::has($breadcrumb['route']))
                    <a
                        href="{{ route($breadcrumb['route']) }}"
                        class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 font-medium text-[var(--color-text-main)]/75 transition hover:bg-[var(--color-bg)] hover:text-[var(--color-primary)]"
                    >
                        @if (! empty($breadcrumb['icon']))
                            <span class="text-xs leading-none">{{ $breadcrumb['icon'] }}</span>
                        @endif

                        <span>{{ $breadcrumb['label'] }}</span>
                    </a>
                @else
                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 font-medium text-[var(--color-text-main)]/75">
                        @if (! empty($breadcrumb['icon']))
                            <span class="text-xs leading-none">{{ $breadcrumb['icon'] }}</span>
                        @endif

                        <span>{{ $breadcrumb['label'] }}</span>
                    </span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
