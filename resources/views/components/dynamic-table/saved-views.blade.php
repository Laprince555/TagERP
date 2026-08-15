@props(['views', 'activeViewId', 'newViewName', 'saveViewError'])

<flux:dropdown>
    <flux:button size="sm" icon="bookmark">
        {{ __('Views') }}
        @if ($activeViewId)
            <span class="ms-1 text-xs text-[var(--color-text-muted)]">
                ({{ collect($views)->firstWhere('id', $activeViewId)['name'] ?? '' }})
            </span>
        @endif
    </flux:button>

    <flux:menu>
        @forelse ($views as $view)
            <flux:menu.item wire:key="saved-view-{{ $view['id'] }}" wire:click="applyView({{ $view['id'] }})">
                <span class="flex-1">
                    {{ $view['name'] }}
                    @if ($view['is_default'])
                        <flux:badge size="sm" color="zinc">{{ __('Default') }}</flux:badge>
                    @endif
                </span>
            </flux:menu.item>
        @empty
            <div class="px-3 py-1.5 text-sm text-muted">{{ __('No saved views yet.') }}</div>
        @endforelse

        <flux:menu.separator />

        @if ($activeViewId)
            @php($active = collect($views)->firstWhere('id', $activeViewId))
            @if ($active)
                <div class="flex items-center gap-1 px-2 py-1">
                    <flux:button
                        size="sm"
                        variant="ghost"
                        wire:click="updateView({{ $active['id'] }})"
                    >
                        {{ __('Save State') }}
                    </flux:button>
                    <flux:button
                        size="sm"
                        variant="ghost"
                        wire:click="setDefaultView({{ $active['id'] }})"
                        :disabled="$active['is_default']"
                    >
                        {{ $active['is_default'] ? __('Default view') : __('Set as default') }}
                    </flux:button>
                    <flux:button size="sm" variant="ghost" wire:click="deleteView({{ $active['id'] }})">
                        {{ __('Delete') }}
                    </flux:button>
                </div>
            @endif
        @endif

        <div class="flex items-center gap-2 px-2 py-1.5">
            <flux:input
                size="sm"
                placeholder="{{ __('Save current as…') }}"
                wire:model="newViewName"
            />
            <flux:button size="sm" wire:click="saveCurrentView($wire.newViewName)">{{ __('Save') }}</flux:button>
        </div>

        @if ($saveViewError)
            <div class="px-2 pb-1 text-xs text-danger">{{ $saveViewError }}</div>
        @endif

        <flux:menu.item wire:click="resetToTableDefaults">
            {{ __('Reset to table defaults') }}
        </flux:menu.item>
    </flux:menu>
</flux:dropdown>
