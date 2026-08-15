@php
    $columnFor = fn ($field) => method_exists($field, 'getColumn') ? $field->getColumn() : $field->getKey();
@endphp

<form wire:submit="save" class="space-y-4">
    @foreach ($definition->fields() as $field)
        @php $column = $columnFor($field); @endphp

        <x-dynamic-component
            :component="$field->component()"
            :field="$field"
            :value="$data[$field->getKey()] ?? null"
            :errors="$errors_[$column] ?? []"
            :form-key="$formKey"
            :relation-results="$relationResults[$field->getKey()] ?? []"
            :relation-has-more="$relationHasMore[$field->getKey()] ?? false"
            :relation-selected="$relationSelected[$field->getKey()] ?? null"
            :cascade-results="$cascadeResults[$field->getKey()] ?? []"
            :cascade-has-more="$cascadeHasMore[$field->getKey()] ?? []"
            :cascade-selected="$cascadeSelected[$field->getKey()] ?? []"
            :is-open="$openCascadeField === $field->getKey() || $activeRelationField === $field->getKey()"
        />
    @endforeach

    <div class="flex justify-end gap-2 pt-2">
        <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
            {{ __('Save') }}
        </flux:button>
    </div>
</form>
