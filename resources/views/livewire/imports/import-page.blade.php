<div class="min-h-screen">
    <section class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <div
            @if (! $import->isFinished()) wire:poll.5s="pollProgress" @endif
            class="rounded-3xl border border-[var(--color-border)] bg-[var(--color-card-bg)] p-6"
        >
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <flux:heading size="lg">{{ $import->filename }}</flux:heading>
                    <flux:subheading>{{ $import->created_at->diffForHumans() }}</flux:subheading>
                </div>

                <flux:badge :color="match ($import->status) {
                    \App\Models\Import::STATUS_COMPLETED => 'green',
                    \App\Models\Import::STATUS_FAILED => 'red',
                    default => 'blue',
                }">
                    {{ __(ucfirst($import->status)) }}
                </flux:badge>
            </div>

            {{-- Zeros and an empty rows table are indistinguishable from failure
                 until the worker picks the job up, so say so in words. --}}
            @if (! $import->isFinished())
                <flux:callout icon="clock" variant="secondary" class="mt-5">
                    <flux:callout.text>
                        {{ $import->status === \App\Models\Import::STATUS_QUEUED
                            ? __('Waiting for a queue worker to pick this up. Rows appear here as soon as the file is read — this page updates itself.')
                            : __('Reading the file and creating records. This page updates itself.') }}
                    </flux:callout.text>
                </flux:callout>
            @endif

            <div class="mt-5 grid grid-cols-3 gap-4">
                <div>
                    <flux:text size="sm" class="uppercase tracking-wide">{{ __('Rows') }}</flux:text>
                    <flux:heading size="lg">{{ $import->total_rows }}</flux:heading>
                </div>
                <div>
                    <flux:text size="sm" class="uppercase tracking-wide">{{ __('Imported') }}</flux:text>
                    <flux:heading size="lg" class="text-green-600">{{ $import->imported_rows }}</flux:heading>
                </div>
                <div>
                    <flux:text size="sm" class="uppercase tracking-wide">{{ __('Failed') }}</flux:text>
                    <flux:heading size="lg" class="text-red-600">{{ $import->failed_rows }}</flux:heading>
                </div>
            </div>

            @if ($import->error)
                <flux:callout icon="exclamation-triangle" variant="danger" class="mt-5">
                    <flux:callout.text>{{ $import->error }}</flux:callout.text>
                </flux:callout>
            @endif

            {{-- Only once every row has been worked through: the file is what a
                 retried job resumes from, so it cannot go while one is pending. --}}
            @if ($import->isFinished())
                <div class="mt-5 flex items-center justify-between gap-3 border-t border-[var(--color-border)] pt-5">
                    @if ($import->isAcknowledged())
                        <flux:text size="sm" class="flex items-center gap-1.5">
                            <flux:icon.check-circle class="size-4 text-green-600" />
                            {{ __('Acknowledged :when — the uploaded file was deleted.', ['when' => $import->acknowledged_at->diffForHumans()]) }}
                        </flux:text>
                    @else
                        <flux:text size="sm">
                            {{ __('Every row has been processed. Confirming deletes the uploaded file; these results are kept.') }}
                        </flux:text>

                        <flux:button
                            variant="primary"
                            icon="check"
                            wire:click="acknowledge"
                            wire:confirm="{{ __('Delete the uploaded file? The results below stay.') }}"
                            class="shrink-0"
                        >
                            {{ __('Acknowledge') }}
                        </flux:button>
                    @endif
                </div>
            @endif
        </div>

        {{-- Nothing is staged until the worker opens the file, and an empty
             table reads as "your import produced nothing". --}}
        @if ($import->total_rows > 0 || $import->isFinished())
            <livewire:imports.import-rows-table :import-id="$import->id" :key="'import-rows-'.$import->id" />
        @endif
    </section>
</div>
