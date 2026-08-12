<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $activeTheme ?? 'orange-onyx' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ __('Create New User') }} - {{ config('app.name', 'TagERP') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="{{ $themeClass ?? 'theme-orange-onyx' }} min-h-screen bg-[var(--color-canvas-bg)] text-[var(--color-text-main)] antialiased" data-theme="{{ $activeTheme ?? 'orange-onyx' }}">
    <header class="border-b border-[var(--color-border)] bg-[var(--color-card-bg)]">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            <a href="{{ url('/') }}" class="text-sm font-bold text-[var(--color-text-main)]">
                {{ config('app.name', 'TagERP') }}
            </a>

            <livewire:components.theme-switcher />
        </div>
    </header>

    <main class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
        <nav class="mb-6 flex items-center gap-2 text-sm text-[var(--color-text-muted)]" aria-label="{{ __('Breadcrumb') }}">
            <span>{{ __('Users') }}</span>
            <span>/</span>
            <span class="font-semibold text-[var(--color-text-main)]">{{ __('Create') }}</span>
        </nav>

        <section class="rounded-xl border border-[var(--color-border)] bg-[var(--color-card-bg)] shadow-sm">
            <div class="border-b border-[var(--color-border)] px-6 py-5">
                <h1 class="text-2xl font-bold tracking-tight text-[var(--color-text-main)]">
                    {{ __('Create New User') }}
                </h1>
                <p class="mt-1 text-sm text-[var(--color-text-muted)]">
                    {{ __('Create an internal TagERP user with the default orange-onyx theme and super_admin role.') }}
                </p>
            </div>

            <div class="px-6 py-6">
                <livewire:admin.create-user-form />
            </div>
        </section>
    </main>

    @livewireScripts
</body>
</html>
