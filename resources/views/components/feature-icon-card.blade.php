@props([
    'icon',
    'title',
    'description',
    'iconClass' => 'bg-[var(--color-primary)]/12 text-[var(--color-primary)]',
    'containerClass' => 'rounded-[1.75rem] border border-[var(--color-border)] bg-[var(--color-canvas-bg)]/78 p-6',
])

<div {{ $attributes->class([$containerClass]) }}>
    <div class="flex items-center gap-3">
        <div class="{{ $iconClass }} flex h-12 w-12 items-center justify-center rounded-2xl">
            <flux:icon :name="$icon" class="h-6 w-6" />
        </div>
        <div>
            <h2 class="text-lg font-black text-[var(--color-text-main)]">{{ $title }}</h2>
            <p class="text-sm text-[var(--color-text-main)]/58">{{ $description }}</p>
        </div>
    </div>
</div>
