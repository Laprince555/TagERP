<div
    wire:key="form-modal-{{ $formKey }}"
    x-data
    x-init="
        $wire.on('form-modal-opened.{{ $formKey }}', () => $flux.modal('create-{{ $formKey }}').show());
        $wire.on('close-form-modal.{{ $formKey }}', () => $flux.modal('create-{{ $formKey }}').close());
    "
>
    <flux:modal name="create-{{ $formKey }}" class="w-full max-w-lg">
        <div class="space-y-4">
            <flux:heading size="lg">{{ $heading }}</flux:heading>

            <livewire:dynamic-form.form :form-key="$formKey" :key="'form-'.$formKey" />
        </div>
    </flux:modal>
</div>
