@php
    $activeLocale = $availableLocales[$currentLocale] ?? null;
@endphp

<flux:dropdown align="end">
    <flux:button
        variant="ghost"
        icon="language"
        icon:trailing="chevron-down"
        class="rounded-2xl border border-[var(--color-border)] bg-[var(--color-card-bg)] text-[var(--color-text-main)] hover:border-[var(--color-primary)]"
        aria-label="Language selector"
    >
        <span class="hidden sm:inline">{{ $activeLocale['native'] ?? strtoupper($currentLocale) }}</span>
    </flux:button>

    <flux:menu>
        @foreach ($availableLocales as $locale => $meta)
            <flux:menu.item
                wire:key="locale-{{ $locale }}"
                wire:click="switchLocale('{{ $locale }}')"
                icon="language"
            >
                <div class="flex min-w-0 items-center gap-3">
                    <span class="font-semibold">{{ $meta['native'] }}</span>
                    <span class="text-[var(--color-text-main)]/55">{{ $meta['label'] }}</span>
                    @if ($currentLocale === $locale)
                        <span class="ml-auto text-[10px] font-bold uppercase tracking-[0.18em] text-[var(--color-primary)]">Active</span>
                    @endif
                </div>
            </flux:menu.item>
        @endforeach
    </flux:menu>
</flux:dropdown>
