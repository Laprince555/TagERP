@props(['columns', 'visibleColumns', 'rows', 'sorts', 'referenceApplications', 'referenceCells' => [], 'relationshipActions' => null])
@php
    use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
@endphp

<div class="overflow-x-auto">
    <flux:table>
        <flux:table.columns>
            @foreach ($visibleColumns as $key)
                @php($column = $columns[$key] ?? null)
                @continue(! $column)
                @php($activeSort = collect($sorts)->firstWhere('column', $key))
                @php($sortPriority = count($sorts) > 1 ? array_search($key, array_column($sorts, 'column'), true) : false)
                @php($sortClickHandler = $column->isSortable() ? '$event.shiftKey ? $wire.sortByAdditive(\''.$key.'\') : $wire.sortBy(\''.$key.'\')' : '')

                <flux:table.column
                    wire:key="header-{{ $key }}"
                    :sortable="$column->isSortable()"
                    :sorted="(bool) $activeSort"
                    :direction="$activeSort['direction'] ?? 'asc'"
                    x-on:click="{{ $sortClickHandler }}"
                >
                    {{ $column->getLabel() }}
                    {{-- Sort priority badge only shown once more than one column is sorted, so the
                         common single-sort case stays visually unchanged. Shift-click a header to
                         add it to a multi-sort. --}}
                    @if ($sortPriority !== false)
                        <span class="ms-1 text-xs text-zinc-400">{{ $sortPriority + 1 }}</span>
                    @endif
                </flux:table.column>
            @endforeach
            @if ($relationshipActions?->isUnlinkable())
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            @endif
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($rows as $row)
                <flux:table.row wire:key="row-{{ $row->getKey() }}">
                    @foreach ($visibleColumns as $key)
                        @php($column = $columns[$key] ?? null)
                        @continue(! $column)
                        <flux:table.cell wire:key="cell-{{ $row->getKey() }}-{{ $key }}">
                            @if ($column instanceof RecordReferenceColumn)
                                @php($referenceCell = $referenceCells[(string) $row->getKey()][$key] ?? null)
                                @if ($referenceCell && $referenceCell['variant'] === 'card')
                                    <x-record-reference.card :identity="$referenceCell['identity']" :facts="$referenceCell['facts']" />
                                @elseif ($referenceCell && $referenceCell['variant'] === 'tag')
                                    <x-record-reference.tag :identity="$referenceCell['identity']" />
                                @elseif ($referenceCell)
                                    <x-record-reference.icon :identity="$referenceCell['identity']" />
                                @endif
                            @else
                                @php($value = data_get($row, str_replace('.', '.', $column->getField())))
                                @php($link = $column->getLink($row))
                                @if ($link)
                                    <a href="{{ $link }}" class="text-[var(--color-accent)] hover:underline">{{ $column->formatValue($value, $row) }}</a>
                                @else
                                    {{ $column->formatValue($value, $row) }}
                                @endif
                            @endif
                        </flux:table.cell>
                    @endforeach
                    @if ($relationshipActions?->isUnlinkable())
                        <flux:table.cell wire:key="cell-{{ $row->getKey() }}-unlink">
                            <flux:button
                                size="sm"
                                variant="danger"
                                wire:click="unlinkRelated({{ $row->getKey() }})"
                                wire:confirm="{{ $relationshipActions->getUnlinkConfirmationText() }}"
                            >
                                {{ __('Unlink') }}
                            </flux:button>
                        </flux:table.cell>
                    @endif
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>
