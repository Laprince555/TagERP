@props([
    'label',
    'value' => '—',
    'icon' => null,
    'hint' => null,
    'comingSoon' => false,
])

<div {{ $attributes->class(['rounded-2xl border border-[var(--color-glass-border)] bg-[var(--color-surface-1)] p-4 shadow-sm']) }}>
    <div class="flex items-center justify-between gap-2">
        <p class="truncate text-[11px] font-black uppercase tracking-[0.18em] text-[var(--color-muted-text)]">{{ $label }}</p>

        @if ($icon)
            <flux:icon :name="$icon" class="h-4 w-4 shrink-0 text-[var(--color-primary)]" />
        @endif
    </div>

    <p class="mt-2 text-2xl font-black tracking-[-0.02em] text-[var(--color-text-main)]">{{ $value }}</p>

    @if ($comingSoon)
        <flux:badge color="zinc" size="sm" class="mt-2">{{ __('messages.workspace.coming_soon') }}</flux:badge>
    @elseif ($hint)
        <p class="mt-2 truncate text-xs text-[var(--color-soft-text)]">{{ $hint }}</p>
    @endif
</div>
