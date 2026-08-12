<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $activeTheme ?? 'orange-onyx' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'TagERP') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="{{ $themeClass ?? 'theme-orange-onyx' }} bg-[var(--color-canvas-bg)] text-[var(--color-text-main)] antialiased" data-theme="{{ $activeTheme ?? 'orange-onyx' }}">
        <header class="border-b border-[var(--color-border)] bg-[var(--color-card-bg)]">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
                <a href="{{ url('/') }}" class="text-sm font-bold text-[var(--color-text-main)]">
                    {{ config('app.name', 'TagERP') }}
                </a>

                <livewire:components.theme-switcher />
            </div>
        </header>

        {{ $slot ?? '' }}

        @livewireScripts
    </body>
</html>
