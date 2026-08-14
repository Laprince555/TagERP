@component('layouts.app')
    <section class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
        <nav class="mb-6 flex items-center gap-2 text-sm text-[var(--color-text-muted)]" aria-label="{{ __('Breadcrumb') }}">
            <span>{{ __('Users') }}</span>
            <span>/</span>
            <span class="font-semibold text-[var(--color-text-main)]">{{ __('Create') }}</span>
        </nav>

        <div class="rounded-[2rem] border border-[var(--color-border)] bg-[var(--color-card-bg)]/92 shadow-xl shadow-[color:var(--color-primary)]/10 backdrop-blur">
            <div class="border-b border-[var(--color-border)] px-6 py-5">
                <h1 class="text-2xl font-[Instrument_Sans] font-black tracking-tight text-[var(--color-text-main)]">
                    {{ __('Create New User') }}
                </h1>
                <p class="mt-1 text-sm text-[var(--color-text-muted)]">
                    {{ __('Create an internal TagERP user with the default orange-onyx theme and super_admin role.') }}
                </p>
            </div>

            <div class="px-6 py-6">
                <livewire:admin.create-user-form />
            </div>
        </div>
    </section>
@endcomponent

