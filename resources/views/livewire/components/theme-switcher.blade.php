<div class="flex items-center gap-2" aria-label="{{ __('Theme selector') }}">
    @foreach ($availableThemes as $theme)
        <button
            type="button"
            wire:key="theme-{{ $theme }}"
            wire:click="switchTheme('{{ $theme }}')"
            class="h-9 w-9 rounded-lg border transition-all focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:ring-offset-2 {{ $currentTheme === $theme ? 'border-[var(--color-primary)] ring-2 ring-[var(--color-primary)]/30' : 'border-[var(--color-border)] hover:border-[var(--color-primary)]' }}"
            title="{{ __(str($theme)->replace('-', ' ')->title()->toString()) }}"
            aria-label="{{ __(str($theme)->replace('-', ' ')->title()->toString()) }}"
        >
            <span class="sr-only">{{ __(str($theme)->replace('-', ' ')->title()->toString()) }}</span>
            <span class="mx-auto block h-4 w-4 rounded-full {{ $theme === 'orange-onyx' ? 'bg-[#f97316]' : '' }} {{ $theme === 'navy-blue' ? 'bg-[#2563eb]' : '' }} {{ $theme === 'emerald-dark' ? 'bg-[#10b981]' : '' }}"></span>
        </button>
    @endforeach
</div>
