<!DOCTYPE html>
<html lang="en" dir="ltr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ __('Sign In') }} - TagERP</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="{{ $themeClass ?? 'theme-orange-onyx' }} bg-[var(--color-canvas-bg)] text-[var(--color-text-main)] font-sans antialiased min-h-screen flex items-center justify-center p-4 sm:p-6 lg:p-8" data-theme="{{ $activeTheme ?? 'orange-onyx' }}">

    <!-- Split-Screen Auth Container -->
    <div class="w-full max-w-5xl overflow-hidden rounded-2xl border border-[var(--color-border)] bg-[var(--color-card-bg)] shadow-2xl grid grid-cols-1 lg:grid-cols-12 min-h-[640px]">
        
        <!-- Left Panel: High-Contrast Sidebar Branding Panel -->
        <div class="lg:col-span-5 bg-[var(--color-sidebar-bg)] text-[var(--color-sidebar-text)] p-8 sm:p-12 flex flex-col justify-between relative overflow-hidden">
            <!-- Background Radial Theme Glows -->
            <div class="absolute -top-24 -left-24 w-72 h-72 bg-[var(--color-primary)]/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-24 -right-24 w-72 h-72 bg-[var(--color-primary-hover)]/15 rounded-full blur-3xl pointer-events-none"></div>

            <!-- Top Header & Dynamic Logo Badge -->
            <div class="relative z-10">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-[var(--color-primary)] text-white flex items-center justify-center shadow-lg shadow-[var(--color-primary)]/30 font-bold text-xl tracking-wider">
                        TAG
                    </div>
                    <div>
                        <h2 class="text-xl font-bold tracking-tight text-[var(--color-sidebar-text)]">TagERP</h2>
                        <p class="text-xs font-medium opacity-70">TagERP Enterprise Architecture</p>
                    </div>
                </div>

                <div class="mt-10">
                    <span class="badge badge-primary text-xs uppercase tracking-wider font-semibold">
                        {{ __('Enterprise WMS & ERP Engine') }}
                    </span>
                    <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-[var(--color-sidebar-text)] leading-tight">
                        {{ __('Precision Inventory & Stock Control') }}
                    </h1>
                    <p class="mt-3 text-sm opacity-80 leading-relaxed">
                        {{ __('Secure, audited, real-time double-entry stock accounting for high-scale enterprise supply chains.') }}
                    </p>
                </div>
            </div>

            <!-- Core Feature Bullet Points -->
            <div class="my-8 relative z-10 space-y-4">
                <div class="flex items-start gap-3 p-3 rounded-lg bg-[var(--color-sidebar-hover)]/60 border border-[var(--color-sidebar-border)] backdrop-blur-sm transition-all hover:border-[var(--color-primary)]/40">
                    <div class="p-2 rounded-md bg-[var(--color-primary)]/15 text-[var(--color-primary)] shrink-0 mt-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-[var(--color-sidebar-text)]">{{ __('Double-Entry Stock Ledger Engine') }}</h4>
                        <p class="text-xs opacity-75">{{ __('Immutable debit & credit transaction tracking across all warehouse locations.') }}</p>
                    </div>
                </div>

                <div class="flex items-start gap-3 p-3 rounded-lg bg-[var(--color-sidebar-hover)]/60 border border-[var(--color-sidebar-border)] backdrop-blur-sm transition-all hover:border-[var(--color-primary)]/40">
                    <div class="p-2 rounded-md bg-[var(--color-primary)]/15 text-[var(--color-primary)] shrink-0 mt-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-[var(--color-sidebar-text)]">{{ __('Real-time WAC Cost Valuation') }}</h4>
                        <p class="text-xs opacity-75">{{ __('Weighted Average Cost calculation recalculated automatically per movement.') }}</p>
                    </div>
                </div>

                <div class="flex items-start gap-3 p-3 rounded-lg bg-[var(--color-sidebar-hover)]/60 border border-[var(--color-sidebar-border)] backdrop-blur-sm transition-all hover:border-[var(--color-primary)]/40">
                    <div class="p-2 rounded-md bg-[var(--color-primary)]/15 text-[var(--color-primary)] shrink-0 mt-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-[var(--color-sidebar-text)]">{{ __('Pessimistic Locking & Anti-Negative Stock Enforcement') }}</h4>
                        <p class="text-xs opacity-75">{{ __('Database row-level locks prevent stock overselling and concurrency race conditions.') }}</p>
                    </div>
                </div>
            </div>

            <!-- Panel Footer -->
            <div class="pt-4 border-t border-[var(--color-sidebar-border)] text-xs opacity-60 flex items-center justify-between relative z-10">
                <span>&copy; {{ date('Y') }} TagERP</span>
                <span class="badge badge-neutral text-[10px]">v1.0.0</span>
            </div>
        </div>

        <!-- Right Panel: Dynamic Form Surface -->
        <div class="lg:col-span-7 bg-[var(--color-card-bg)] text-[var(--color-text-main)] p-8 sm:p-12 flex flex-col justify-between">
            <div>
                <!-- Form Header -->
                <div class="mb-8">
                    <h2 class="text-2xl font-bold tracking-tight text-[var(--color-text-main)]">
                        {{ __('Sign In to TagERP') }}
                    </h2>
                    <p class="mt-1 text-sm text-[var(--color-text-muted)]">
                        {{ __('Enter your credentials to access your enterprise dashboard.') }}
                    </p>
                </div>

                <!-- Session Status Message -->
                @if (session('status'))
                    <div class="mb-6 p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-sm font-medium text-emerald-800 flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                <!-- Authentication Form -->
                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf

                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-[var(--color-text-main)] mb-2">
                            {{ __('Email Address') }}
                        </label>
                        <div class="relative rounded-lg shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[var(--color-text-muted)]">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                                </svg>
                            </div>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="administrator@tagerp.com"
                                class="w-full pl-10 pr-4 py-3 rounded-lg border text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] @error('email') border-rose-500 bg-rose-50/30 text-rose-900 @else border-[var(--color-border)] bg-[var(--color-card-bg)] text-[var(--color-text-main)] @enderror"
                            >
                        </div>
                        @error('email')
                            <p class="mt-2 text-xs font-semibold text-rose-600 flex items-center gap-1">
                                <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <span>{{ $message }}</span>
                            </p>
                        @enderror
                    </div>

                    <!-- Password Field -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-[var(--color-text-main)]">
                                {{ __('Password') }}
                            </label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-xs font-medium text-[var(--color-primary)] hover:text-[var(--color-primary-hover)] transition-colors focus:outline-none focus:underline">
                                    {{ __('Forgot password?') }}
                                </a>
                            @endif
                        </div>
                        <div class="relative rounded-lg shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[var(--color-text-muted)]">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                placeholder="••••••••••••"
                                class="w-full pl-10 pr-4 py-3 rounded-lg border text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] @error('password') border-rose-500 bg-rose-50/30 text-rose-900 @else border-[var(--color-border)] bg-[var(--color-card-bg)] text-[var(--color-text-main)] @enderror"
                            >
                        </div>
                        @error('password')
                            <p class="mt-2 text-xs font-semibold text-rose-600 flex items-center gap-1">
                                <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <span>{{ $message }}</span>
                            </p>
                        @enderror
                    </div>

                    <!-- Remember Me Checkbox -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2.5 cursor-pointer select-none">
                            <input
                                type="checkbox"
                                name="remember"
                                id="remember_me"
                                class="w-4 h-4 rounded border-[var(--color-border)] text-[var(--color-primary)] focus:ring-[var(--color-primary)] transition-colors cursor-pointer"
                            >
                            <span class="text-xs font-medium text-[var(--color-text-main)]">
                                {{ __('Remember me on this device') }}
                            </span>
                        </label>
                    </div>

                    <!-- Primary Submit Button -->
                    <div>
                        <button
                            type="submit"
                            class="w-full py-3 px-4 rounded-lg bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white font-bold text-sm shadow-md shadow-[var(--color-primary)]/25 transition-all transform active:scale-[0.99] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:ring-offset-2 flex items-center justify-center gap-2"
                        >
                            <span>{{ __('Sign In to TagERP') }}</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Dynamic Footer Notice -->
            <div class="mt-8 pt-6 border-t border-[var(--color-border)]">
                <div class="p-3.5 rounded-lg bg-[var(--color-canvas-bg)] border border-[var(--color-border)] flex items-start gap-2.5">
                    <svg class="w-4 h-4 text-[var(--color-text-muted)] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-xs text-[var(--color-text-muted)] leading-relaxed">
                        {{ __('Public registration is disabled. Please contact your System Administrator for access provisioning.') }}
                    </p>
                </div>
            </div>
        </div>

    </div>
@livewireScripts
</body>
</html>
