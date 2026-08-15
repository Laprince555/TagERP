@props([
    'field',
    'value',
    'errors' => [],
    'formKey',
    'relationResults' => [],
    'relationHasMore' => false,
    'relationSelected' => null,
    'isOpen' => false,
])

@php
    $fieldKey = $field->getKey();
    $selected = $relationSelected;
@endphp

{{--
    Fixed-position overlay driven by server-rendered open state, for the
    same reasons as the cascading field: a nested <flux:modal> is destroyed
    by Livewire's morph on the request that opens it, an inline panel gets
    clipped inside the modal box, and teleporting to <body> lands outside
    the open dialog where everything is inert. See that component for the
    full note.
--}}
<div wire:key="relation-list-{{ $formKey }}-{{ $fieldKey }}">
    <flux:field>
        <flux:label>{{ $field->getLabel() }}@if ($field->isRequired()) <span class="text-red-500">*</span> @endif</flux:label>

        <flux:button
            type="button"
            :variant="$isOpen ? 'filled' : 'ghost'"
            class="w-full justify-start"
            {{-- NB: @js() is NOT compiled inside a Blade *component* attribute
                 (it renders literally and the click silently does nothing).
                 Use {{ }} interpolation on <flux:*> elements. --}}
            wire:click="toggleRelationPicker('{{ $fieldKey }}')"
        >
            {{ $selected['label'] ?? $field->getPlaceholder() ?? __('Select…') }}
        </flux:button>

        @if ($field->getHelpText())
            <flux:description>{{ $field->getHelpText() }}</flux:description>
        @endif

        @foreach ($errors as $message)
            <flux:error>{{ $message }}</flux:error>
        @endforeach
    </flux:field>

    @if ($isOpen)
        <div
            class="fixed inset-0 z-[9998] bg-black/40"
            wire:click="toggleRelationPicker('{{ $fieldKey }}')"
        ></div>

        <div
            class="fixed left-1/2 top-1/2 z-[9999] w-[min(28rem,calc(100vw-2rem))] -translate-x-1/2 -translate-y-1/2 space-y-2 overflow-y-auto rounded-xl border border-zinc-200 bg-white p-4 shadow-2xl dark:border-zinc-700 dark:bg-zinc-800"
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
                    wire:click="toggleRelationPicker('{{ $fieldKey }}')"
                />
            </div>

            <flux:input
                wire:model.live.debounce.400ms="relationSearch.{{ $fieldKey }}"
                type="search"
                :placeholder="__('Search…')"
                autocomplete="off"
                size="sm"
            />

            <div
                wire:loading.class="opacity-50"
                wire:target="relationSearch.{{ $fieldKey }},loadMoreRelation"
                class="max-h-52 space-y-1 overflow-y-auto"
            >
                @forelse ($relationResults as $result)
                    <button
                        type="button"
                        wire:key="candidate-{{ $fieldKey }}-{{ $result['id'] }}"
                        wire:click="chooseRelation(@js($fieldKey), @js($result['id']))"
                        class="w-full rounded-md border border-zinc-200 px-3 py-2 text-start text-sm hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800"
                    >
                        {{ $result['label'] }}
                    </button>
                @empty
                    <flux:text class="text-xs text-zinc-500">{{ __('No results.') }}</flux:text>
                @endforelse
            </div>

            @if ($relationHasMore)
                <flux:button
                    size="sm"
                    variant="ghost"
                    wire:click="loadMoreRelation('{{ $fieldKey }}')"
                    wire:loading.attr="disabled"
                    wire:target="loadMoreRelation"
                >
                    {{ __('Load more') }}
                </flux:button>
            @endif
        </div>
    @endif
</div>
