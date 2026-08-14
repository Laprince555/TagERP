{{--
    The scoped event names below (dashes/colons from instanceIdentifier())
    are NOT safe to use as Alpine x-on: directive modifiers (Alpine parses
    every dot-separated segment after the event name as a modifier, and the
    identifier can itself contain dots/colons). $wire.on() takes the event
    name as a plain JS string instead, so it's unaffected by that.
--}}
<div
    wire:key="relation-picker-{{ $modalName }}"
    x-data
    x-init="
        $wire.on('relation-picker-opened.{{ $modalName }}', () => $flux.modal('{{ $modalName }}').show());
        $wire.on('close-relation-picker.{{ $modalName }}', () => $flux.modal('{{ $modalName }}').close());
    "
>
    <flux:modal name="{{ $modalName }}" class="w-full max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Link existing record') }}</flux:heading>

            <flux:input
                wire:model.live.debounce.400ms="search"
                type="search"
                :placeholder="__('Search…')"
                autocomplete="off"
            />

            @if ($error)
                <flux:text class="text-red-600">{{ $error }}</flux:text>
            @endif

            <div wire:loading.class="opacity-50" wire:target="search,loadMore" class="max-h-72 space-y-1 overflow-y-auto">
                @forelse ($results as $result)
                    <button
                        type="button"
                        wire:key="candidate-{{ $result['id'] }}"
                        wire:click="selectCandidate({{ $result['id'] }})"
                        @class([
                            'w-full rounded-md border px-3 py-2 text-start text-sm',
                            'border-[var(--color-accent)] bg-[var(--color-accent)]/10' => (string) $selectedId === (string) $result['id'],
                            'border-zinc-200 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800' => (string) $selectedId !== (string) $result['id'],
                        ])
                    >
                        {{ $result['label'] }}
                    </button>
                @empty
                    <flux:text class="text-zinc-500">{{ __('No results.') }}</flux:text>
                @endforelse
            </div>

            @if ($hasMore)
                <flux:button size="sm" variant="ghost" wire:click="loadMore" wire:loading.attr="disabled" wire:target="loadMore">
                    {{ __('Load more') }}
                </flux:button>
            @elseif ($picker && count($results) >= $picker->getMaximumLoadedResults())
                <flux:text class="text-sm text-zinc-500">{{ __('Refine your search to see more results.') }}</flux:text>
            @endif

            <div class="flex justify-end gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <flux:button variant="ghost" x-on:click="$flux.modal('{{ $modalName }}').close()">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" wire:click="confirmLink" :disabled="$selectedId === null">
                    {{ __('Link') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
