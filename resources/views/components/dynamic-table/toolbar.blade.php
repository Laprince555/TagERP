@props(['tableKey', 'perPage'])

<div class="flex flex-wrap items-center justify-between gap-3 pb-4">
    <x-dynamic-table.search />

    <flux:select wire:change="setPerPage($event.target.value)" size="sm" class="max-w-[8rem]">
        @foreach (\App\Support\DynamicTable\Core\TableState::PER_PAGE_OPTIONS as $option)
            <flux:select.option value="{{ $option }}" :selected="$option === $perPage">{{ __(':count / page', ['count' => $option]) }}</flux:select.option>
        @endforeach
    </flux:select>
</div>
