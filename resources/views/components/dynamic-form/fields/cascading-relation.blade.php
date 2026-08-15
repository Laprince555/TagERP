@props([
    'field',
    'value',
    'errors' => [],
    'formKey',
    'cascadeResults' => [],
    'cascadeHasMore' => [],
    'cascadeSelected' => [],
    'isOpen' => false,
])

@php
    $fieldKey = $field->getKey();
    $levels = $field->getLevels();

    // "Country|State|City" until picked, then "Egypt|Giza|Faisal".
    $buttonText = $field->displayText($cascadeSelected);
@endphp

{{--
    A fixed-position overlay driven by server-rendered open state — NOT a
    nested <flux:modal>, and NOT an inline block.

    * Not a nested modal: this form normally renders inside FormModal's
      <dialog>, and a dialog nested in an already-open dialog is re-created
      by Livewire's DOM morph on the very request that opens it, so the show
      event lands on a detached element and nothing appears.
    * Not inline: the panel would then be clipped inside the modal's box.
    * Not x-teleport'd to <body>: everything outside an open modal <dialog>
      is inert, so it would render but refuse clicks.

    Staying inside the dialog subtree while positioned fixed puts it above
    the whole page and keeps it interactive.
--}}
<div wire:key="cascade-{{ $formKey }}-{{ $fieldKey }}">
    <flux:field>
        <flux:button
            type="button"
            icon="map-pin"
            :variant="$isOpen ? 'filled' : 'ghost'"
            class="w-full justify-start"
            {{-- NB: @js() is NOT compiled inside a Blade *component* attribute
                 (it renders literally and the click silently does nothing).
                 Use {{ }} interpolation on <flux:*> elements. --}}
            wire:click="toggleCascadePicker('{{ $fieldKey }}')"
        >
            {{ $buttonText }}@if ($field->isRequired()) <span class="text-red-500">*</span> @endif
        </flux:button>

        @if ($field->getHelpText())
            <flux:description>{{ $field->getHelpText() }}</flux:description>
        @endif

        @foreach ($errors as $message)
            <flux:error>{{ $message }}</flux:error>
        @endforeach
    </flux:field>

    @if ($isOpen)
        {{-- Backdrop: dims everything and closes on click. --}}
        <div
            class="fixed inset-0 z-[9998] bg-black/40"
            wire:click="closeCascadePicker"
        ></div>

        <div
            class="fixed left-1/2 top-1/2 z-[9999] w-[min(28rem,calc(100vw-2rem))] -translate-x-1/2 -translate-y-1/2 space-y-3 overflow-y-auto rounded-xl border border-zinc-200 bg-white p-4 shadow-2xl dark:border-zinc-700 dark:bg-zinc-800"
            style="max-height: min(32rem, calc(100vh - 4rem));"
        >
            <div class="flex items-center justify-between gap-2">
                <flux:heading size="lg">{{ $field->getLabel() }}</flux:heading>

                <flux:button
                    type="button"
                    size="sm"
                    variant="ghost"
                    icon="x-mark"
                    :aria-label="__('Close')"
                    wire:click="closeCascadePicker"
                />
            </div>

            @foreach ($levels as $level)
                @php
                    $levelKey = $level->getKey();
                    $levelLabel = str($levelKey)->headline()->toString();
                    $selected = $cascadeSelected[$levelKey] ?? null;
                    $unlocked = $this->cascadeLevelUnlocked($fieldKey, $levelKey);
                    $results = $cascadeResults[$levelKey] ?? [];
                @endphp

                <div wire:key="cascade-level-{{ $formKey }}-{{ $fieldKey }}-{{ $levelKey }}" @class(['opacity-60' => ! $unlocked])>
                    <div class="mb-1 flex items-center justify-between gap-2">
                        <flux:text class="text-sm font-medium">{{ $levelLabel }}</flux:text>

                        @if ($selected)
                            <div class="flex items-center gap-2">
                                <flux:badge size="sm" color="emerald">{{ $selected['label'] }}</flux:badge>
                                <flux:button
                                    size="xs"
                                    variant="ghost"
                                    wire:click="reopenCascadeLevel('{{ $fieldKey }}', '{{ $levelKey }}')"
                                >
                                    {{ __('Change') }}
                                </flux:button>
                            </div>
                        @endif
                    </div>

                    @if (! $unlocked)
                        <flux:text class="text-xs text-zinc-500">
                            {{ __('Choose a :parent first.', ['parent' => str($field->parentLevel($levelKey)?->getKey() ?? '')->headline()->toString()]) }}
                        </flux:text>
                    @elseif (! $selected)
                        <flux:input
                            wire:model.live.debounce.400ms="cascadeSearch.{{ $fieldKey }}.{{ $levelKey }}"
                            type="search"
                            :placeholder="__('Search :name…', ['name' => $levelLabel])"
                            autocomplete="off"
                            size="sm"
                        />

                        <div
                            wire:loading.class="opacity-50"
                            wire:target="cascadeSearch.{{ $fieldKey }}.{{ $levelKey }},loadMoreCascade"
                            class="mt-1 max-h-40 space-y-1 overflow-y-auto"
                        >
                            @forelse ($results as $result)
                                <button
                                    type="button"
                                    wire:key="cascade-opt-{{ $levelKey }}-{{ $result['id'] }}"
                                    wire:click="chooseCascade(@js($fieldKey), @js($levelKey), @js($result['id']))"
                                    class="w-full rounded-md border border-zinc-200 px-3 py-2 text-start text-sm hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800"
                                >
                                    {{ $result['label'] }}
                                </button>
                            @empty
                                <flux:text class="text-xs text-zinc-500">{{ __('No results.') }}</flux:text>
                            @endforelse
                        </div>

                        @if ($cascadeHasMore[$levelKey] ?? false)
                            <flux:button
                                size="sm"
                                variant="ghost"
                                class="mt-1"
                                wire:click="loadMoreCascade('{{ $fieldKey }}', '{{ $levelKey }}')"
                                wire:loading.attr="disabled"
                                wire:target="loadMoreCascade"
                            >
                                {{ __('Load more') }}
                            </flux:button>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
