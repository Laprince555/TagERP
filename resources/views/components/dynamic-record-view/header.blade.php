@props(['title', 'subtitle' => null, 'code' => null])

<div class="flex flex-col gap-2 rounded-[1.75rem] border border-[var(--color-glass-border)] bg-[var(--color-surface-1)] p-5 sm:p-6 shadow-lg shadow-[var(--color-card-shadow)]">
    <div class="flex flex-wrap items-center gap-3">
        <flux:heading size="xl" level="1">{{ $title }}</flux:heading>

        @if ($code)
            <flux:badge color="zinc" size="sm">{{ $code }}</flux:badge>
        @endif
    </div>

    @if ($subtitle)
        <p class="text-sm text-[var(--color-soft-text)]">{{ $subtitle }}</p>
    @endif

    {{ $slot ?? '' }}
</div>
