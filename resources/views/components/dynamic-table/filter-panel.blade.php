@props(['filters', 'draft', 'belongsToSelectedLabels' => [], 'belongsToOptions' => [], 'belongsToSearch' => [], 'activeFilterChips' => [], 'hasDraftFilterChanges' => false])

@if (count($activeFilterChips) > 0)
    <div class="mb-2 flex flex-wrap items-center gap-1.5">
        @foreach ($activeFilterChips as $chip)
            <span wire:key="active-filter-chip-{{ $chip['key'] }}" class="inline-flex items-center gap-1 rounded-full bg-[var(--color-accent)]/10 px-2.5 py-1 text-xs text-[var(--color-accent)]">
                <strong>{{ $chip['label'] }}:</strong> {{ $chip['summary'] }}
                <button type="button" wire:click="clearFilter('{{ $chip['key'] }}')" aria-label="{{ __('Remove :label filter', ['label' => $chip['label']]) }}" class="ms-0.5 hover:opacity-70">&times;</button>
            </span>
        @endforeach
        <button type="button" wire:click="clearFilters" class="text-xs text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
            {{ __('Clear all') }}
        </button>
    </div>
@endif

@if (count($filters) > 0)
    <div x-data="{ open: false }" class="mb-4 rounded-lg border border-[var(--color-border)]">
        <button
            type="button"
            x-on:click="open = !open"
            class="flex w-full items-center justify-between px-4 py-2 text-sm font-medium"
        >
            {{ __('Filters') }}
            <svg x-show="!open" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
            <svg x-show="open" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" /></svg>
        </button>

        <div x-show="open" style="display: none;" class="border-t border-[var(--color-border)] p-4">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            @foreach ($filters as $key => $filter)
                <div wire:key="filter-{{ $key }}">
                    <flux:field>
                        <flux:label>{{ $filter->getLabel() }}</flux:label>

                        @if ($filter instanceof \App\Support\DynamicTable\Core\Filters\TextFilter)
                            <div class="flex gap-2">
                                <flux:select wire:model="filters.{{ $key }}.operator" size="sm">
                                    @foreach ($filter->getOperators() as $operator)
                                        <flux:select.option value="{{ $operator->value }}">{{ str($operator->name)->headline() }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <x-dynamic-table.filter-value-input :filter-key="$key" input-type="text" />
                            </div>
                        @elseif ($filter instanceof \App\Support\DynamicTable\Core\Filters\NumberFilter)
                            <div class="flex gap-2">
                                <flux:select wire:model="filters.{{ $key }}.operator" size="sm">
                                    @foreach ($filter->getOperators() as $operator)
                                        <flux:select.option value="{{ $operator->value }}">{{ str($operator->name)->headline() }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <x-dynamic-table.filter-value-input :filter-key="$key" input-type="number" />
                            </div>
                        @elseif ($filter instanceof \App\Support\DynamicTable\Core\Filters\DateFilter)
                            <div class="flex gap-2">
                                <flux:select wire:model="filters.{{ $key }}.operator" size="sm">
                                    @foreach ($filter->getOperators() as $operator)
                                        <flux:select.option value="{{ $operator->value }}">{{ str($operator->name)->headline() }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <x-dynamic-table.filter-value-input :filter-key="$key" :input-type="$filter->hasTime() ? 'datetime-local' : 'date'" />
                            </div>
                        @elseif ($filter instanceof \App\Support\DynamicTable\Core\Filters\BooleanFilter)
                            <flux:select wire:model="filters.{{ $key }}.value" size="sm">
                                <flux:select.option value="">{{ __('Any') }}</flux:select.option>
                                <flux:select.option value="1">{{ __('Yes') }}</flux:select.option>
                                <flux:select.option value="0">{{ __('No') }}</flux:select.option>
                            </flux:select>
                        @elseif ($filter instanceof \App\Support\DynamicTable\Core\Filters\EnumFilter)
                            <flux:select wire:model="filters.{{ $key }}.value" size="sm" multiple="{{ $filter->isMultiple() }}">
                                @foreach ($filter->getEnumClass()::cases() as $case)
                                    <flux:select.option value="{{ $case->value }}">{{ str($case->name)->headline() }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        @elseif ($filter instanceof \App\Support\DynamicTable\Core\Filters\BelongsToFilter)
                            <div x-data="{ open: false }" class="relative">
                                @php($selected = $belongsToSelectedLabels[$key] ?? [])

                                @if ($filter->isMultiple() && count($selected) > 0)
                                    <div class="mb-1.5 flex flex-wrap gap-1">
                                        @foreach ($selected as $option)
                                            <span wire:key="belongsto-chip-{{ $key }}-{{ $option['id'] }}" class="inline-flex items-center gap-1 rounded-full bg-zinc-100 dark:bg-zinc-700 px-2 py-0.5 text-xs">
                                                {{ $option['label'] }}
                                                <button type="button" wire:click="removeBelongsToOption('{{ $key }}', {{ $option['id'] }})" aria-label="{{ __('Remove') }}">&times;</button>
                                            </span>
                                        @endforeach
                                    </div>
                                @elseif (! $filter->isMultiple() && count($selected) > 0)
                                    <div class="mb-1.5 text-sm">{{ __('Selected: ') }}{{ $selected[0]['label'] }}</div>
                                @endif

                                <flux:input
                                    size="sm"
                                    placeholder="{{ __('Search…') }}"
                                    wire:model="belongsToSearch.{{ $key }}"
                                    wire:input.debounce.500ms="searchBelongsTo('{{ $key }}', $event.target.value)"
                                    x-on:focus="open = true"
                                    autocomplete="off"
                                />

                                <div
                                    x-show="open"
                                    x-on:click.outside="open = false"
                                    class="absolute z-10 mt-1 max-h-48 w-full overflow-y-auto rounded-md border border-[var(--color-border)] bg-white shadow-lg dark:bg-zinc-800"
                                    style="display: none;"
                                >
                                    @forelse (($belongsToOptions[$key] ?? []) as $option)
                                        <button
                                            type="button"
                                            wire:key="belongsto-option-{{ $key }}-{{ $option['id'] }}"
                                            wire:click="selectBelongsToOption('{{ $key }}', {{ $option['id'] }})"
                                            x-on:click="open = false"
                                            class="block w-full px-3 py-1.5 text-start text-sm hover:bg-zinc-50 dark:hover:bg-zinc-700"
                                        >
                                            {{ $option['label'] }}
                                        </button>
                                    @empty
                                        <div class="px-3 py-1.5 text-sm text-zinc-400">
                                            {{ mb_strlen($belongsToSearch[$key] ?? '') < \App\Livewire\DynamicTable\Table::BELONGS_TO_MIN_SEARCH_LENGTH ? __('Type to search…') : __('No matches.') }}
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        @endif
                    </flux:field>
                </div>
            @endforeach
        </div>

        <div class="mt-4 flex items-center gap-2">
            <flux:button wire:click="applyFilters" size="sm" variant="primary">{{ __('Apply') }}</flux:button>
            <flux:button wire:click="clearFilters" size="sm" variant="ghost">{{ __('Clear') }}</flux:button>
            @if ($hasDraftFilterChanges)
                <span class="text-xs text-amber-600 dark:text-amber-400">{{ __('Unapplied changes') }}</span>
            @endif
        </div>
        </div>
    </div>
@endif
