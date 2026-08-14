@props([
    'label',
    'value',
])

<div class="rounded-3xl border border-[var(--color-border)] bg-[var(--color-canvas-bg)]/75 p-5">
    <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--color-text-main)]/45">{{ $label }}</p>
    <p class="mt-3 text-lg font-bold text-[var(--color-text-main)]">{{ $value }}</p>
</div>
