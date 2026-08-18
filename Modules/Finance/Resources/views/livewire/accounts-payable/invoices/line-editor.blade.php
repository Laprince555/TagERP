<div class="min-h-screen">
    <section class="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-6">
            <flux:heading size="xl">{{ $invoice->invoice_number }}</flux:heading>
            <flux:text class="mt-1">{{ $invoice->code }}</flux:text>

            <flux:button
                :href="route('finance.accounts-payable.invoices.show', ['recordId' => $invoice->getKey()])"
                wire:navigate
                size="sm"
                variant="ghost"
                icon="arrow-left"
                class="mt-3"
            >
                {{ __('Back to invoice') }}
            </flux:button>
        </div>

        @if ($flash)
            <flux:callout variant="danger" class="mb-4" :text="$flash" />
        @endif

        <div class="space-y-3">
            @foreach ($rows as $index => $row)
                <div wire:key="line-{{ $row['id'] ?? 'new-'.$index }}" class="flex items-end gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                    <div class="flex-1">
                        <flux:textarea wire:model="rows.{{ $index }}.description" :label="__('Description')" rows="1" />
                    </div>
                    <div class="w-28">
                        <flux:input type="number" step="any" wire:model="rows.{{ $index }}.quantity" :label="__('Quantity')" />
                    </div>
                    <div class="w-36">
                        <flux:input type="number" step="any" wire:model="rows.{{ $index }}.unit_price" :label="__('Unit Price')" />
                    </div>
                    <flux:button wire:click="removeRow({{ $index }})" variant="ghost" icon="trash" :aria-label="__('Remove line')" />
                </div>
            @endforeach
        </div>

        <div class="mt-4 flex items-center justify-between">
            <flux:button wire:click="addRow" variant="filled" icon="plus">{{ __('Add Line') }}</flux:button>
            <flux:button wire:click="save" variant="primary">{{ __('Save Lines') }}</flux:button>
        </div>
    </section>
</div>
