<div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
    <flux:heading size="xl" level="1" class="mb-6">{{ $definition->title() }}</flux:heading>

    <livewire:dynamic-form.form :form-key="$formKey" :key="'form-'.$formKey" />
</div>
