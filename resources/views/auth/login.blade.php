<!DOCTYPE html>
<html lang="en" dir="ltr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ __('Sign In') }} - TagERP</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="{{ $themeClass ?? 'theme-orange-onyx' }} bg-[var(--color-canvas-bg)] text-[var(--color-text-main)] font-sans antialiased min-h-screen flex items-center justify-center p-4 sm:p-6 lg:p-8" data-theme="{{ $activeTheme ?? 'orange-onyx' }}">
    <div class="grid min-h-[640px] w-full max-w-5xl grid-cols-1 overflow-hidden rounded-2xl border border-[var(--color-border)] bg-[var(--color-card-bg)] shadow-2xl lg:grid-cols-12">
        <div class="relative flex flex-col justify-between overflow-hidden bg-[var(--color-sidebar-bg)] p-8 text-[var(--color-sidebar-text)] sm:p-12 lg:col-span-5">
            <div class="pointer-events-none absolute -top-24 -left-24 h-72 w-72 rounded-full bg-[var(--color-primary)]/20 blur-3xl"></div>
            <div class="pointer-events-none absolute -right-24 -bottom-24 h-72 w-72 rounded-full bg-[var(--color-primary-hover)]/15 blur-3xl"></div>

            <div class="relative z-10">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-[var(--color-primary)] text-xl font-bold tracking-wider text-white shadow-lg shadow-[var(--color-primary)]/30">
                        TAG
                    </div>
                    <div>
                        <h2 class="text-xl font-bold tracking-tight text-[var(--color-sidebar-text)]">TagERP</h2>
                        <p class="text-xs font-medium opacity-70">TagERP Enterprise Architecture</p>
                    </div>
                </div>

                <div class="mt-10">
                    <span class="badge badge-primary text-xs font-semibold uppercase tracking-wider">
                        {{ __('Enterprise WMS & ERP Engine') }}
                    </span>
                    <h1 class="mt-3 text-3xl font-extrabold leading-tight tracking-tight text-[var(--color-sidebar-text)]">
                        {{ __('Precision Inventory & Stock Control') }}
                    </h1>
                    <p class="mt-3 text-sm leading-relaxed opacity-80">
                        {{ __('Secure, audited, real-time double-entry stock accounting for high-scale enterprise supply chains.') }}
                    </p>
                </div>
            </div>

            <div class="relative z-10 my-8 space-y-4">
                <x-feature-icon-card
                    icon="shield-check"
                    :title="__('Double-Entry Stock Ledger Engine')"
                    :description="__('Immutable debit & credit transaction tracking across all warehouse locations.')"
                    container-class="rounded-lg border border-[var(--color-sidebar-border)] bg-[var(--color-sidebar-hover)]/60 p-3 backdrop-blur-sm transition-all hover:border-[var(--color-primary)]/40"
                    icon-class="bg-[var(--color-primary)]/15 text-[var(--color-primary)]"
                />

                <x-feature-icon-card
                    icon="currency-dollar"
                    :title="__('Real-time WAC Cost Valuation')"
                    :description="__('Weighted Average Cost calculation recalculated automatically per movement.')"
                    container-class="rounded-lg border border-[var(--color-sidebar-border)] bg-[var(--color-sidebar-hover)]/60 p-3 backdrop-blur-sm transition-all hover:border-[var(--color-primary)]/40"
                    icon-class="bg-[var(--color-primary)]/15 text-[var(--color-primary)]"
                />

                <x-feature-icon-card
                    icon="lock-closed"
                    :title="__('Pessimistic Locking & Anti-Negative Stock Enforcement')"
                    :description="__('Database row-level locks prevent stock overselling and concurrency race conditions.')"
                    container-class="rounded-lg border border-[var(--color-sidebar-border)] bg-[var(--color-sidebar-hover)]/60 p-3 backdrop-blur-sm transition-all hover:border-[var(--color-primary)]/40"
                    icon-class="bg-[var(--color-primary)]/15 text-[var(--color-primary)]"
                />
            </div>

            <div class="relative z-10 flex items-center justify-between border-t border-[var(--color-sidebar-border)] pt-4 text-xs opacity-60">
                <span>&copy; {{ date('Y') }} TagERP</span>
                <span class="badge badge-neutral text-[10px]">v1.0.0</span>
            </div>
        </div>

        <div class="flex flex-col justify-between bg-[var(--color-card-bg)] p-8 text-[var(--color-text-main)] sm:p-12 lg:col-span-7">
            <div>
                <div class="mb-8">
                    <h2 class="text-2xl font-bold tracking-tight text-[var(--color-text-main)]">
                        {{ __('Sign In to TagERP') }}
                    </h2>
                    <p class="mt-1 text-sm text-[var(--color-text-muted)]">
                        {{ __('Enter your credentials to access your enterprise dashboard.') }}
                    </p>
                </div>

                @if (session('status'))
                    <div class="mb-6 flex items-center gap-2 rounded-lg border border-ledger-in bg-ledger-in-subtle p-4 text-sm font-medium text-ledger-in">
                        <svg class="h-5 w-5 shrink-0 text-ledger-in" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf

                    <flux:field>
                        <flux:label for="email">{{ __('Email Address') }}</flux:label>
                        <flux:input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="administrator@tagerp.com"
                        />
                        <flux:error name="email" />
                    </flux:field>

                    <div class="space-y-3">
                        <div class="mb-2 flex items-center justify-between">
                            <flux:label for="password">{{ __('Password') }}</flux:label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-xs font-medium text-[var(--color-primary)] transition-colors hover:text-[var(--color-primary-hover)] focus:underline focus:outline-none">
                                    {{ __('Forgot password?') }}
                                </a>
                            @endif
                        </div>

                        <flux:input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••••••"
                        />
                        <flux:error name="password" />
                    </div>

                    <div class="flex items-center justify-between">
                        <flux:field variant="inline">
                            <flux:checkbox name="remember" id="remember_me" />
                            <flux:label for="remember_me">{{ __('Remember me on this device') }}</flux:label>
                        </flux:field>
                    </div>

                    <div>
                        <flux:button type="submit" variant="primary" class="w-full">
                            {{ __('Sign In to TagERP') }}
                        </flux:button>
                    </div>
                </form>
            </div>

            <div class="mt-8 border-t border-[var(--color-border)] pt-6">
                <div class="flex items-start gap-2.5 rounded-lg border border-[var(--color-border)] bg-[var(--color-canvas-bg)] p-3.5">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-[var(--color-text-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-xs leading-relaxed text-[var(--color-text-muted)]">
                        {{ __('Public registration is disabled. Please contact your System Administrator for access provisioning.') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    @livewireScripts
</body>
</html>
