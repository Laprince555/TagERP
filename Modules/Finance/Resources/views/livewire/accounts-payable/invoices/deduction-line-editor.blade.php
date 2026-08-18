<div class="min-h-screen">
    <section class="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-6">
            <flux:heading size="xl">{{ $invoice->invoice_number }}</flux:heading>
            <flux:text class="mt-1">{{ __('Deduction Lines') }}</flux:text>

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
                <div wire:key="deduction-line-{{ $row['id'] ?? 'new-'.$index }}" class="flex items-end gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                    <div class="flex-1">
                        <flux:select wire:model="rows.{{ $index }}.deduction_id" :label="__('Deduction')">
                            <flux:select.option value="">{{ __('Select a deduction') }}</flux:select.option>
                            @foreach ($this->deductionOptions as $deduction)
                                <flux:select.option value="{{ $deduction->id }}">{{ $deduction->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                    <div class="flex-1">
                        <flux:select wire:model="rows.{{ $index }}.cost_center_id" :label="__('Cost Center')">
                            <flux:select.option value="">{{ __('Follows invoice') }}</flux:select.option>
                            @foreach ($this->costCenterOptions as $costCenter)
                                <flux:select.option value="{{ $costCenter->id }}">{{ $costCenter->number }} — {{ $costCenter->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                    <div class="w-36">
                        <flux:input type="number" step="any" wire:model="rows.{{ $index }}.amount" :label="__('Amount')" />
                    </div>
                    <flux:button wire:click="removeRow({{ $index }})" variant="ghost" icon="trash" :aria-label="__('Remove line')" />
                </div>
            @endforeach
        </div>

        <div class="mt-4 flex items-center justify-between">
            <flux:button wire:click="addRow" variant="filled" icon="plus">{{ __('Add Deduction Line') }}</flux:button>
            <flux:button wire:click="save" variant="primary">{{ __('Save Deduction Lines') }}</flux:button>
        </div>
    </section>
</div>
