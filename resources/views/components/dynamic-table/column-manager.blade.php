@props(['columns', 'visible', 'columnOrder' => []])

@php
    $fixedColumns = collect($columns)->filter(fn ($column) => ! $column->isToggleable());
    // Toggleable columns are listed in the user's persisted/current columnOrder, not raw
    // definition order — otherwise the manager itself would contradict a completed reorder.
    $toggleableColumns = collect($columnOrder)
        ->mapWithKeys(fn ($key) => [$key => $columns[$key] ?? null])
        ->filter(fn ($column, $key) => $column && $column->isToggleable());
@endphp

<flux:dropdown class="mb-4">
    <flux:button size="sm" icon="view-columns">{{ __('Columns') }}</flux:button>

    <flux:menu>
        @foreach ($fixedColumns as $key => $column)
            <flux:menu.item wire:key="column-fixed-{{ $key }}" disabled>
                <flux:checkbox checked disabled />
                {{ $column->getLabel() }}
                <span class="ms-auto text-xs text-muted">{{ __('Fixed') }}</span>
            </flux:menu.item>
        @endforeach

        {{--
            Drag-and-drop reorder via Livewire 4's wire:sort — one request per completed
            drop (SortableJS only fires onSort once, on drag end), not per pointer move.
            Keyboard-accessible fallback: the Up/Down buttons call the same authorization-safe
            sortColumns() action for users who can't drag-and-drop.
        --}}
        <ul wire:sort="sortColumns" class="contents">
            @foreach ($toggleableColumns as $key => $column)
                <li wire:sort:item="{{ $key }}" wire:key="column-toggle-{{ $key }}" class="group flex items-center">
                    <span class="cursor-move px-1 text-muted" aria-hidden="true">&#8942;&#8942;</span>

                    <flux:menu.item wire:click.stop="toggleColumn('{{ $key }}')" class="flex-1">
                        <flux:checkbox :checked="in_array($key, $visible, true)" />
                        {{ $column->getLabel() }}
                    </flux:menu.item>

                    <span class="hidden gap-0.5 pe-1 group-hover:flex">
                        <button
                            type="button"
                            wire:click.stop="sortColumns('{{ $key }}', {{ $loop->index - 1 }})"
                            @if ($loop->first) disabled @endif
                            aria-label="{{ __('Move up') }}"
                            class="rounded px-1 text-xs text-muted hover:text-main disabled:opacity-30"
                        >&uarr;</button>
                        <button
                            type="button"
                            wire:click.stop="sortColumns('{{ $key }}', {{ $loop->index + 1 }})"
                            @if ($loop->last) disabled @endif
                            aria-label="{{ __('Move down') }}"
                            class="rounded px-1 text-xs text-muted hover:text-main disabled:opacity-30"
                        >&darr;</button>
                    </span>
                </li>
            @endforeach
        </ul>
    </flux:menu>
</flux:dropdown>
