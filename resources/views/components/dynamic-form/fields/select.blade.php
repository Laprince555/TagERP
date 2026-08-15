@props(['field', 'value', 'errors' => [], 'formKey'])

<flux:field>
    <flux:label>{{ $field->getLabel() }}@if ($field->isRequired()) <span class="text-red-500">*</span> @endif</flux:label>

    <flux:select wire:model="data.{{ $field->getKey() }}">
        <flux:select.option value="">{{ $field->getPlaceholder() ?? __('Select...') }}</flux:select.option>
        @foreach ($field->getOptions() as $optionValue => $optionLabel)
            <flux:select.option value="{{ $optionValue }}">{{ $optionLabel }}</flux:select.option>
        @endforeach
    </flux:select>

    @if ($field->getHelpText())
        <flux:description>{{ $field->getHelpText() }}</flux:description>
    @endif

    @foreach ($errors as $message)
        <flux:error>{{ $message }}</flux:error>
    @endforeach
</flux:field>
