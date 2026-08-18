@php
    $themeMeta = [
        'orange-onyx' => ['label' => 'Orange Onyx', 'swatch' => 'bg-[#f97316]', 'icon' => 'sun'],
        'navy-blue' => ['label' => 'Navy Blue', 'swatch' => 'bg-[#2563eb]', 'icon' => 'moon'],
        'emerald-dark' => ['label' => 'Emerald Dark', 'swatch' => 'bg-[#10b981]', 'icon' => 'sparkles'],
        'palestine' => [
            'label' => 'Palestine',
            'swatch' => '',
            'icon' => 'flag',
            'style' => 'background: linear-gradient(90deg, #CE1126 0 40%, transparent 40%), linear-gradient(180deg, #0A0A0A 0 33.33%, #FFFFFF 33.33% 66.66%, #007A3D 66.66%);',
        ],
        'forest-acid' => ['label' => 'Forest Acid', 'swatch' => 'bg-[#B7FF72]', 'icon' => 'moon'],
        'violet-mist' => ['label' => 'Violet Mist', 'swatch' => 'bg-[#7A35FF]', 'icon' => 'sun'],
        'inkberry-peach' => ['label' => 'Inkberry Peach', 'swatch' => 'bg-[#FFB7A5]', 'icon' => 'moon'],
    ];
    $activeTheme = $themeMeta[$currentTheme] ?? $themeMeta['orange-onyx'];
@endphp

<flux:dropdown align="end">
    <flux:button
        variant="ghost"
        icon="{{ $activeTheme['icon'] }}"
        icon:trailing="chevron-down"
        class="rounded-2xl border border-[var(--color-border)] bg-[var(--color-card-bg)] text-[var(--color-text-main)] hover:border-[var(--color-primary)]"
        aria-label="{{ __('Theme selector') }}"
    >
        <span class="hidden sm:inline">{{ $activeTheme['label'] }}</span>
    </flux:button>

    <flux:menu>
        @foreach ($availableThemes as $theme)
            <flux:menu.item
                wire:key="theme-{{ $theme }}"
                wire:click="switchTheme('{{ $theme }}')"
                icon="{{ $themeMeta[$theme]['icon'] }}"
            >
                <div class="flex min-w-0 items-center gap-3">
                    <span class="h-3 w-3 shrink-0 rounded-full {{ $themeMeta[$theme]['swatch'] }}" style="{{ $themeMeta[$theme]['style'] ?? '' }}"></span>
                    <span>{{ $themeMeta[$theme]['label'] }}</span>
                    @if ($currentTheme === $theme)
                        <span class="ml-auto text-[10px] font-bold uppercase tracking-[0.18em] text-[var(--color-primary)]">Active</span>
                    @endif
                </div>
            </flux:menu.item>
        @endforeach
    </flux:menu>
</flux:dropdown>
