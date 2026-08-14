{{-- Search is submit-triggered (Enter/button), not live. If changed to live search later,
     wire:model.live must carry at least a 400ms debounce. --}}
<form wire:submit="submitSearch" class="flex items-center gap-2">
    <flux:input wire:model="search" type="search" placeholder="{{ __('Search...') }}" size="sm" />
    <flux:button type="submit" size="sm" variant="filled">{{ __('Search') }}</flux:button>
</form>
