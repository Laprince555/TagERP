@props(['tableKey', 'perPage', 'createFormKey' => null, 'createFormLabel' => null])

<div class="flex flex-wrap items-center justify-between gap-3 pb-4">
    <x-dynamic-table.search />

    <div class="flex items-center gap-3">
        @if ($createFormKey)
            <flux:button size="sm" variant="primary" icon="plus" wire:click="$dispatch('open-form-modal.{{ $createFormKey }}')">
                {{ $createFormLabel ?? __('Create') }}
            </flux:button>
        @endif

        @if ($this->selectAllMatching || count($this->selectedIds) > 0)
            <span class="text-sm text-muted">
                {{ $this->selectAllMatching ? __('All matching selected') : __(':count selected', ['count' => count($this->selectedIds)]) }}
            </span>
            <flux:button size="sm" wire:click="clearSelection">{{ __('Clear') }}</flux:button>
            @if ($this->canBulkDelete())
                <flux:button
                    size="sm"
                    variant="danger"
                    wire:click="bulkDelete"
                    wire:confirm="{{ __('Delete the selected records? This cannot be undone.') }}"
                >
                    {{ __('Delete selected') }}
                </flux:button>
            @endif
        @endif

        <flux:button size="sm" wire:click="export" wire:target="export">
            {{ __('Export CSV') }}
        </flux:button>

        <flux:select wire:change="setPerPage($event.target.value)" size="sm" class="max-w-[8rem]">
            @foreach (\App\Support\DynamicTable\Core\TableState::PER_PAGE_OPTIONS as $option)
                <flux:select.option value="{{ $option }}" :selected="$option === $perPage">{{ __(':count / page', ['count' => $option]) }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>
</div>
