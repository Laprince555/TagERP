@php
    $transaction = $this->transaction();
@endphp

<div class="min-h-screen">
    <div class="mx-auto max-w-5xl px-4 pt-6 sm:px-6 lg:px-8">
        <livewire:general.breadcrumbs :trailing="$transaction->code" />
    </div>

    <x-general::workspace.application-header
        :application="$application"
        :sub-module="$subModule"
        :module="$module"
    />

    <section class="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-6">
            <flux:heading size="xl">{{ $transaction->code }}</flux:heading>
            <flux:text class="mt-1">{{ $transaction->cycle?->name }} · {{ $transaction->status->label() }}</flux:text>

            <flux:button
                :href="route('hr.cycles.transactions.show', ['recordId' => $transaction->getKey()])"
                wire:navigate
                size="sm"
                variant="ghost"
                icon="arrow-left"
                class="mt-3"
            >
                {{ __('Back to transaction') }}
            </flux:button>
        </div>

        @if ($error)
            <flux:callout variant="danger" icon="exclamation-triangle" class="mb-4">
                {{ $error }}
            </flux:callout>
        @endif

        <flux:field class="mb-4">
            <flux:label>{{ __('Note (optional)') }}</flux:label>
            <flux:input wire:model="note" placeholder="{{ __('Reason, context…') }}" />
        </flux:field>

        <div class="space-y-3">
            @foreach ($transaction->lines as $line)
                <flux:card wire:key="cycle-transaction-line-{{ $line->id }}">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <flux:text class="font-semibold">{{ $line->sequence }}. {{ $line->name }}</flux:text>
                            <flux:text class="text-zinc-500">{{ $line->jobTitle?->name }}@if($line->jobGrade) · {{ $line->jobGrade->name }}@endif</flux:text>
                        </div>

                        <div class="flex items-center gap-2">
                            <flux:badge
                                :color="match ($line->status->value) {
                                    'approved' => 'green',
                                    'rejected' => 'red',
                                    default => 'zinc',
                                }"
                            >
                                {{ $line->status->label() }}
                            </flux:badge>

                            @if ($line->status->value === 'pending')
                                <flux:button wire:click="approve({{ $line->id }})" size="sm" variant="primary" icon="check">
                                    {{ __('Approve') }}
                                </flux:button>
                                <flux:button wire:click="reject({{ $line->id }})" size="sm" variant="danger" icon="x-mark">
                                    {{ __('Reject') }}
                                </flux:button>
                            @endif
                        </div>
                    </div>
                </flux:card>
            @endforeach
        </div>
    </section>
</div>
