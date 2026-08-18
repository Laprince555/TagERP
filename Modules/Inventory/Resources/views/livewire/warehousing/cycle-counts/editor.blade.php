@php
    $cycleCount = $this->cycleCount();
    $editable = $this->isEditable();
@endphp

<div class="min-h-screen">
    <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
        <livewire:general.breadcrumbs :trailing="$cycleCount->code" />
    </div>

    <x-general::workspace.application-header
        :application="$application"
        :sub-module="$subModule"
        :module="$module"
    />

    <section class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ $cycleCount->code }}</flux:heading>
                <flux:text class="mt-1">{{ $cycleCount->count_date->toDateString() }}</flux:text>

                <flux:button
                    :href="route('inventory.warehousing.cycle-counts.show', ['recordId' => $cycleCount->getKey()])"
                    wire:navigate
                    size="sm"
                    variant="ghost"
                    icon="arrow-left"
                    class="mt-3"
                >
                    {{ __('Back to cycle count') }}
                </flux:button>
            </div>

            <flux:badge color="zinc" size="lg">
                {{ \Modules\Inventory\System\Warehousing\CycleCountStatus::from($cycleCount->status)->label() }}
            </flux:badge>
        </div>

        @if ($flash)
            <flux:callout variant="success" icon="check-circle" class="mb-4" x-data x-init="setTimeout(() => $wire.set('flash', null), 4000)">
                {{ $flash }}
            </flux:callout>
        @endif

        @if (! $editable)
            <flux:callout variant="secondary" icon="lock-closed" class="mb-4">
                {{ __('Counts can only be entered while a cycle count is in progress.') }}
            </flux:callout>
        @endif

        <flux:card class="overflow-hidden p-0">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[56rem] text-sm">
                    <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                        <tr class="text-start text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                            <th class="w-10 px-3 py-2 text-start">#</th>
                            <th class="min-w-40 px-3 py-2 text-start">{{ __('Item ID') }}</th>
                            <th class="min-w-40 px-3 py-2 text-start">{{ __('Location ID') }}</th>
                            <th class="w-32 px-3 py-2 text-end">{{ __('System Qty') }}</th>
                            <th class="w-32 px-3 py-2 text-end">{{ __('Physical Qty') }}</th>
                            <th class="w-10 px-3 py-2"></th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($rows as $index => $row)
                            <tr wire:key="cc-row-{{ $index }}-{{ $row['id'] ?? 'new' }}" class="align-top">
                                <td class="px-3 py-2 text-zinc-400 tabular-nums">{{ $index + 1 }}</td>

                                <td class="px-3 py-2">
                                    <flux:input size="sm" type="number" wire:model="rows.{{ $index }}.item_id" :disabled="! $editable" />
                                    <flux:error name="rows.{{ $index }}.item_id" />
                                </td>

                                <td class="px-3 py-2">
                                    <flux:input size="sm" type="number" wire:model="rows.{{ $index }}.location_id" :disabled="! $editable" />
                                    <flux:error name="rows.{{ $index }}.location_id" />
                                </td>

                                <td class="px-3 py-2">
                                    <flux:input size="sm" inputmode="decimal" class="text-end tabular-nums" wire:model="rows.{{ $index }}.system_qty" :disabled="! $editable" />
                                </td>

                                <td class="px-3 py-2">
                                    <flux:input size="sm" inputmode="decimal" class="text-end tabular-nums" wire:model="rows.{{ $index }}.physical_qty" :disabled="! $editable" placeholder="—" />
                                    <flux:error name="rows.{{ $index }}.physical_qty" />
                                </td>

                                <td class="px-3 py-2 text-center">
                                    @if ($editable)
                                        <flux:button
                                            size="xs"
                                            variant="subtle"
                                            icon="x-mark"
                                            wire:click="removeRow({{ $index }})"
                                            aria-label="{{ __('Remove line') }} {{ $index + 1 }}"
                                        />
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </flux:card>

        @if ($editable)
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                <flux:button size="sm" variant="subtle" icon="plus" wire:click="addRow">
                    {{ __('Add line') }}
                </flux:button>

                <flux:button variant="primary" wire:click="saveDraft" wire:loading.attr="disabled" wire:target="saveDraft">
                    {{ __('Save counts') }}
                </flux:button>
            </div>
        @endif
    </section>
</div>
