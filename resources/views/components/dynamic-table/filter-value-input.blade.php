@props(['filterKey', 'inputType'])

{{--
    Alpine mirrors the deferred operator value via $wire.entangle() (no extra
    Livewire request — entangle stays local until the next natural sync, same
    as a plain wire:model) so the value control can react to the selected
    operator instantly: no value input at all for is_empty/is_not_empty/
    today/yesterday/this_week/this_month, two inputs for between/not_between,
    one input otherwise.
--}}
<div x-data="{ op: $wire.entangle('filters.{{ $filterKey }}.operator') }">
    <template x-if="!['is_empty', 'is_not_empty', 'today', 'yesterday', 'this_week', 'this_month'].includes(op)">
        <div>
            <template x-if="['between', 'not_between'].includes(op)">
                <div class="flex gap-2">
                    <flux:input type="{{ $inputType }}" wire:model="filters.{{ $filterKey }}.value.0" size="sm" placeholder="{{ __('From') }}" />
                    <flux:input type="{{ $inputType }}" wire:model="filters.{{ $filterKey }}.value.1" size="sm" placeholder="{{ __('To') }}" />
                </div>
            </template>
            <template x-if="!['between', 'not_between'].includes(op)">
                <flux:input type="{{ $inputType }}" wire:model="filters.{{ $filterKey }}.value" size="sm" />
            </template>
        </div>
    </template>
</div>
