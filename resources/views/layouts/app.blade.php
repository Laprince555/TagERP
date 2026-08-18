<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\LocaleOptions::direction() }}" data-theme="{{ $activeTheme ?? 'orange-onyx' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'TagERP') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        @fluxAppearance

        <style>
            :root {
                --flux-surface: color-mix(in srgb, var(--color-card-bg) 94%, var(--color-canvas-bg) 6%);
                --flux-surface-strong: color-mix(in srgb, var(--color-card-bg) 82%, var(--color-sidebar-bg) 18%);
                --flux-border: color-mix(in srgb, var(--color-border) 84%, var(--color-text-main) 16%);
                --flux-text: var(--color-text-main);
                --flux-soft-text: color-mix(in srgb, var(--color-text-main) 74%, transparent);
                --flux-muted-text: color-mix(in srgb, var(--color-text-main) 58%, transparent);
                --flux-active: color-mix(in srgb, var(--color-primary) 16%, var(--color-card-bg) 84%);
                --flux-danger-active: color-mix(in srgb, #ef4444 18%, var(--color-card-bg) 82%);
            }

            [data-flux-menu] {
                background-color: var(--flux-surface) !important;
                border-color: var(--flux-border) !important;
                color: var(--flux-text) !important;
                box-shadow:
                    0 18px 48px color-mix(in srgb, var(--color-canvas-bg) 52%, transparent),
                    0 0 0 1px color-mix(in srgb, var(--flux-border) 72%, transparent) !important;
            }

            [data-flux-menu] [data-flux-menu-item] {
                color: var(--flux-text) !important;
            }

            [data-flux-heading] {
                color: var(--color-text-main) !important;
            }

            .breadcrumb-theme-fix,
            .breadcrumb-theme-fix a,
            .breadcrumb-theme-fix svg,
            .breadcrumb-theme-fix span {
                color: var(--color-primary) !important;
                opacity: 0.7;
                transition: all 0.2s ease-in-out;
            }

            .breadcrumb-theme-fix a:hover,
            .breadcrumb-theme-fix a:hover svg,
            .breadcrumb-theme-fix a:hover span {
                color: var(--color-primary-hover) !important;
                opacity: 1;
            }

            .breadcrumb-theme-fix [aria-current="page"],
            .breadcrumb-theme-fix [data-current] {
                color: var(--color-primary) !important;
                opacity: 1;
            }

            [data-flux-menu] [data-flux-menu-item]:hover,
            [data-flux-menu] [data-flux-menu-item][data-active] {
                background-color: var(--flux-active) !important;
            }

            [data-flux-menu] [data-flux-menu-item][variant='danger']:hover,
            [data-flux-menu] [data-flux-menu-item][variant='danger'][data-active] {
                background-color: var(--flux-danger-active) !important;
            }

            [data-flux-menu] [data-flux-menu-item-icon],
            [data-flux-menu] [data-flux-menu-item] [class*='text-zinc-400'],
            [data-flux-menu] [data-flux-menu-item] [class*='text-white/60'] {
                color: var(--flux-muted-text) !important;
            }

            [data-flux-menu] [data-flux-menu-item]:hover [data-flux-menu-item-icon],
            [data-flux-menu] [data-flux-menu-item][data-active] [data-flux-menu-item-icon] {
                color: var(--flux-text) !important;
            }

            [data-flux-menu-separator] {
                background-color: var(--flux-border) !important;
            }

            :is([data-theme='emerald-dark'], [data-theme='forest-acid'], [data-theme='inkberry-peach']) header {
                background-color: color-mix(in srgb, var(--color-card-bg) 94%, var(--color-canvas-bg) 6%) !important;
            }

            :is([data-theme='emerald-dark'], [data-theme='forest-acid'], [data-theme='inkberry-peach']) header [data-flux-button],
            :is([data-theme='emerald-dark'], [data-theme='forest-acid'], [data-theme='inkberry-peach']) header [data-flux-avatar],
            :is([data-theme='emerald-dark'], [data-theme='forest-acid'], [data-theme='inkberry-peach']) header a,
            :is([data-theme='emerald-dark'], [data-theme='forest-acid'], [data-theme='inkberry-peach']) header p,
            :is([data-theme='emerald-dark'], [data-theme='forest-acid'], [data-theme='inkberry-peach']) header span {
                color: var(--color-text-main);
            }

            :is([data-theme='emerald-dark'], [data-theme='forest-acid'], [data-theme='inkberry-peach']) header [data-flux-button] {
                background-color: color-mix(in srgb, var(--color-card-bg) 96%, var(--color-canvas-bg) 4%) !important;
                border-color: color-mix(in srgb, var(--color-border) 92%, var(--color-text-main) 8%) !important;
            }

            :is([data-theme='emerald-dark'], [data-theme='forest-acid'], [data-theme='inkberry-peach']) header [data-flux-button]:hover {
                border-color: var(--color-primary) !important;
            }

            :is([data-theme='emerald-dark'], [data-theme='forest-acid'], [data-theme='inkberry-peach']) header .text-\[var\(--color-text-main\)\]\/50,
            :is([data-theme='emerald-dark'], [data-theme='forest-acid'], [data-theme='inkberry-peach']) header .text-\[var\(--color-text-main\)\]\/55 {
                color: color-mix(in srgb, var(--color-text-main) 78%, transparent) !important;
            }
        </style>
    </head>
    <body class="{{ $themeClass ?? 'theme-orange-onyx' }} bg-[radial-gradient(circle_at_top_left,var(--color-primary-soft),transparent_28%),linear-gradient(180deg,var(--color-surface-0),var(--color-canvas-bg))] text-[var(--color-text-main)] antialiased" data-theme="{{ $activeTheme ?? 'orange-onyx' }}">
        @php
            $authUser = auth()->user();
            $userName = $authUser?->name ?? 'Workspace User';
            $userEmail = $authUser?->email ?? '';
        @endphp

        @persist('app-header')
        <header class="sticky top-0 z-40 border-b border-[var(--color-border)] bg-[var(--color-card-bg)]/92 backdrop-blur-xl">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-3 sm:px-6 lg:px-8">
                <a wire:navigate href="{{ route('launcher') }}" class="flex items-center gap-3 text-[var(--color-text-main)]">
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[var(--color-primary)] text-sm font-black text-white shadow-lg shadow-[var(--color-primary)]/25">
                        TE
                    </span>

                    <span class="min-w-0">
                        <span class="block truncate text-sm font-black tracking-wide">
                            {{ config('app.name', 'TagERP') }}
                        </span>
                        <span class="block text-[11px] font-medium uppercase tracking-[0.24em] text-[var(--color-text-main)]/50">
                            Suite
                        </span>
                    </span>
                </a>

                <div class="flex items-center gap-2 sm:gap-3">
                    <flux:button
                        type="button"
                        variant="ghost"
                        icon="magnifying-glass"
                        x-data
                        x-on:click="window.dispatchEvent(new CustomEvent('toggle-command-palette'))"
                        class="rounded-2xl border border-[var(--color-border)] bg-[var(--color-card-bg)] text-[var(--color-text-main)] hover:border-[var(--color-primary)]"
                    >
                        <span class="hidden md:inline">Quick Search</span>
                        <span class="hidden rounded-lg border border-[var(--color-border)] bg-[var(--color-canvas-bg)] px-2 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-[var(--color-text-main)]/55 lg:inline-flex">
                            Ctrl K
                        </span>
                    </flux:button>

                    <livewire:components.notification-bell />

                    <livewire:components.language-switcher />

                    <livewire:components.theme-switcher />

                    <flux:dropdown align="end" x-data>
                        <flux:button variant="ghost" class="rounded-2xl border border-[var(--color-border)] bg-[var(--color-card-bg)] px-2 py-2 text-[var(--color-text-main)] hover:border-[var(--color-primary)]">
                            <div class="flex items-center gap-3">
                                <flux:avatar
                                    circle
                                    size="sm"
                                    color="auto"
                                    :name="$userName"
                                    :color:seed="$userEmail !== '' ? $userEmail : $userName"
                                />

                                <div class="hidden min-w-0 text-left sm:block">
                                    <p class="truncate text-sm font-bold">{{ $userName }}</p>
                                    <p class="truncate text-xs text-[var(--color-text-main)]/55">{{ $userEmail !== '' ? $userEmail : 'Account' }}</p>
                                </div>
                            </div>
                        </flux:button>

                        <flux:menu>
                            <flux:menu.item wire:navigate href="{{ route('profile') }}" icon="user">
                                Profile
                            </flux:menu.item>
                            <flux:menu.item wire:navigate href="{{ route('settings') }}" icon="cog-6-tooth">
                                Settings
                            </flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item icon="arrow-left-start-on-rectangle" variant="danger" x-on:click="$refs.logoutForm.submit()">
                                Sign out
                            </flux:menu.item>
                            <form x-ref="logoutForm" method="POST" action="{{ route('logout') }}" class="hidden">
                                @csrf
                            </form>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </div>
        </header>
        @endpersist

        @persist('app-overlays')
        <livewire:system.command-palette />
        <livewire:record-reference.preview-host />
        @endpersist

        <main data-current-route="{{ request()->route()?->getName() }}">
            @if (($showBreadcrumbs ?? true) !== false)
                <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
                    <livewire:general.breadcrumbs />
                </div>
            @endif

            {{ $slot ?? '' }}
        </main>

        <flux:toast position="bottom end" />

        @livewireScripts
        @fluxScripts
        @stack('scripts')
    </body>
</html>
