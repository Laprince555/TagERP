@props(['field', 'value', 'errors' => [], 'formKey'])

<flux:field>
    <flux:label>{{ $field->getLabel() }}@if ($field->isRequired()) <span class="text-red-500">*</span> @endif</flux:label>

    <flux:textarea
        wire:model="data.{{ $field->getKey() }}"
        placeholder="{{ $field->getPlaceholder() }}"
        rows="{{ $field->getRows() }}"
    />

    @if ($field->getHelpText())
        <flux:description>{{ $field->getHelpText() }}</flux:description>
    @endif

    @foreach ($errors as $message)
        <flux:error>{{ $message }}</flux:error>
    @endforeach
</flux:field>
