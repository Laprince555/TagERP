<form wire:submit="submit" class="space-y-6">
    @if ($successMessage)
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ $successMessage }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label for="name" class="mb-2 block text-xs font-semibold uppercase tracking-wider text-[var(--color-text-main)]">
                {{ __('Name') }}
            </label>
            <input id="name" type="text" wire:model="name" autocomplete="name" class="w-full rounded-lg border px-4 py-3 text-sm transition-colors focus:border-[var(--color-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] @error('name') border-rose-500 bg-rose-50/30 text-rose-900 @else border-[var(--color-border)] bg-[var(--color-card-bg)] text-[var(--color-text-main)] @enderror">
            @error('name')
                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="sm:col-span-2">
            <label for="email" class="mb-2 block text-xs font-semibold uppercase tracking-wider text-[var(--color-text-main)]">
                {{ __('Email Address') }}
            </label>
            <input id="email" type="email" wire:model="email" autocomplete="username" class="w-full rounded-lg border px-4 py-3 text-sm transition-colors focus:border-[var(--color-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] @error('email') border-rose-500 bg-rose-50/30 text-rose-900 @else border-[var(--color-border)] bg-[var(--color-card-bg)] text-[var(--color-text-main)] @enderror">
            @error('email')
                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="mb-2 block text-xs font-semibold uppercase tracking-wider text-[var(--color-text-main)]">
                {{ __('Password') }}
            </label>
            <input id="password" type="password" wire:model="password" autocomplete="new-password" class="w-full rounded-lg border px-4 py-3 text-sm transition-colors focus:border-[var(--color-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] @error('password') border-rose-500 bg-rose-50/30 text-rose-900 @else border-[var(--color-border)] bg-[var(--color-card-bg)] text-[var(--color-text-main)] @enderror">
            @error('password')
                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="mb-2 block text-xs font-semibold uppercase tracking-wider text-[var(--color-text-main)]">
                {{ __('Confirm Password') }}
            </label>
            <input id="password_confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password" class="w-full rounded-lg border border-[var(--color-border)] bg-[var(--color-card-bg)] px-4 py-3 text-sm text-[var(--color-text-main)] transition-colors focus:border-[var(--color-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
        </div>
    </div>

    <div class="flex items-center justify-end gap-3 border-t border-[var(--color-border)] pt-5">
        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-[var(--color-primary)] px-5 py-3 text-sm font-bold text-white shadow-md shadow-[var(--color-primary)]/25 transition-all hover:bg-[var(--color-primary-hover)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-70" wire:loading.attr="disabled">
            <span wire:loading.remove>{{ __('Create User') }}</span>
            <span wire:loading>{{ __('Creating...') }}</span>
        </button>
    </div>
</form>
