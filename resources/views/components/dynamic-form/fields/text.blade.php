@props(['field', 'value', 'errors' => [], 'formKey'])

<flux:field>
    <flux:label>{{ $field->getLabel() }}@if ($field->isRequired()) <span class="text-danger">*</span> @endif</flux:label>

    <flux:input
        type="{{ $field->getInputType() }}"
        wire:model="data.{{ $field->getKey() }}"
        placeholder="{{ $field->getPlaceholder() }}"
    />

    @if ($field->getHelpText())
        <flux:description>{{ $field->getHelpText() }}</flux:description>
    @endif

    @foreach ($errors as $message)
        <flux:error>{{ $message }}</flux:error>
    @endforeach
</flux:field>
