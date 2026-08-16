@php
    // A nested create form lives inside its parent form's picker, and a
    // <form> inside a <form> is dropped by the browser — taking the nested
    // submit with it. Same body, submitted by click instead. wire:submit is
    // left on both: it is simply inert on a <div>.
    $tag = $nested ? 'div' : 'form';
@endphp

<{{ $tag }} wire:submit="save" class="space-y-4">
    @foreach ($definition->fields() as $field)
        <x-dynamic-component
            :component="$field->component()"
            :field="$field"
            :value="$data[$field->getKey()] ?? null"
            :errors="$errors->get('data.'.$field->getKey())"
            :form-key="$formKey"
            :relation-results="$relationResults[$field->getKey()] ?? []"
            :relation-has-more="$relationHasMore[$field->getKey()] ?? false"
            :relation-selected="$relationSelected[$field->getKey()] ?? null"
            :cascade-results="$cascadeResults[$field->getKey()] ?? []"
            :cascade-has-more="$cascadeHasMore[$field->getKey()] ?? []"
            :cascade-selected="$cascadeSelected[$field->getKey()] ?? []"
            :is-open="$openCascadeField === $field->getKey() || $activeRelationField === $field->getKey()"
            :create-form-key="$this->createFormKeyFor($field->getKey())"
            :create-is-open="$openCreateField === $field->getKey()"
        />
    @endforeach

    <div class="flex justify-end gap-2 pt-2">
        @if ($nested)
            <flux:button type="button" variant="primary" wire:click="save" wire:loading.attr="disabled" wire:target="save">
                {{ __('Save') }}
            </flux:button>
        @else
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                {{ __('Save') }}
            </flux:button>
        @endif
    </div>
</{{ $tag }}>
