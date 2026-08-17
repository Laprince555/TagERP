<div
    wire:key="form-modal-{{ $modalKey }}"
    x-data
    x-init="
        $wire.on('form-modal-opened.{{ $modalKey }}', () => $flux.modal('create-{{ $modalKey }}').show());
        $wire.on('close-form-modal.{{ $modalKey }}', () => $flux.modal('create-{{ $modalKey }}').close());
    "
>
    <flux:modal name="create-{{ $modalKey }}" class="w-full max-w-lg">
        <div class="space-y-4">
            <flux:heading size="lg">{{ $heading }}</flux:heading>

            <livewire:dynamic-form.form :form-key="$formKey" :record-id="$recordId" :copy="$copy" :key="'form-'.$modalKey.'-'.($recordId ?? 'new')" />
        </div>
    </flux:modal>
</div>
