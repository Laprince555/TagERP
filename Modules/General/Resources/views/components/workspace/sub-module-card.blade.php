@props([
    'subModule',
])

@php
    $url = $subModule['url'] ?? null;
    $isAvailable = filled($url);
    $tag = $isAvailable ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if ($isAvailable)
        href="{{ $url }}"
        aria-label="{{ __('messages.workspace.open_sub_module', ['name' => $subModule['name']]) }}"
    @else
        aria-disabled="true"
    @endif
    {{ $attributes->class([
        'group relative flex min-h-[13rem] flex-col overflow-hidden rounded-[1.5rem] border border-[var(--color-glass-border)] bg-[var(--color-surface-1)] p-5 shadow-lg shadow-[var(--color-card-shadow)] transition duration-300',
        'hover:-translate-y-1 hover:border-[var(--color-primary)]/55 hover:shadow-2xl focus-visible:-translate-y-1 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)] focus-visible:ring-offset-2 focus-visible:ring-offset-[var(--color-canvas-bg)]' => $isAvailable,
        'opacity-70' => ! $isAvailable,
    ]) }}
>
    <div class="absolute inset-x-0 top-0 h-1.5 bg-[linear-gradient(90deg,var(--color-primary),var(--color-accent))] opacity-0 transition duration-300 group-hover:opacity-100"></div>

    <div class="flex items-start justify-between gap-3">
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[linear-gradient(135deg,var(--color-primary-soft),var(--color-accent-soft))] text-[var(--color-primary)] shadow-inner">
            <flux:icon :name="$subModule['icon']" class="h-6 w-6" />
        </div>

        <div class="flex flex-col items-end gap-2">
            <flux:badge color="zinc" size="sm">
                {{ trans_choice('messages.workspace.application_count', $subModule['application_count'], ['count' => $subModule['application_count']]) }}
            </flux:badge>

            @unless ($isAvailable)
                <flux:badge color="amber" size="sm">{{ __('messages.workspace.coming_soon') }}</flux:badge>
            @endunless
        </div>
    </div>

    <div class="mt-4 flex-1 space-y-2">
        <flux:heading size="lg" class="line-clamp-2">{{ $subModule['name'] }}</flux:heading>

        <p class="line-clamp-3 text-sm leading-6 text-[var(--color-soft-text)]">
            {{ $subModule['description'] !== '' ? $subModule['description'] : __('messages.workspace.no_description') }}
        </p>
    </div>

    <div class="mt-5 flex items-center justify-between gap-3 border-t border-[var(--color-glass-border)] pt-4">
        <span class="truncate font-mono text-xs font-bold uppercase tracking-[0.14em] text-[var(--color-muted-text)]">{{ $subModule['code'] }}</span>

        @if ($isAvailable)
            <span class="flex shrink-0 items-center gap-2 text-[var(--color-primary)] transition duration-300 group-hover:translate-x-1 rtl:group-hover:-translate-x-1">
                <span class="text-xs font-black uppercase tracking-[0.18em]">{{ __('messages.workspace.open') }}</span>
                <flux:icon name="arrow-right" class="h-4 w-4 rtl:rotate-180" />
            </span>
        @else
            <span class="flex shrink-0 items-center gap-2 text-[var(--color-muted-text)]">
                <span class="text-xs font-black uppercase tracking-[0.18em]">{{ __('messages.workspace.unavailable') }}</span>
                <flux:icon name="lock-closed" class="h-4 w-4" />
            </span>
        @endif
    </div>
</{{ $tag }}>
