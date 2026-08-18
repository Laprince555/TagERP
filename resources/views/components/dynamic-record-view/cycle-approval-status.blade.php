@props(['record'])

@php
    $transaction = method_exists($record, 'cycleTransactions')
        ? $record->cycleTransactions()
            ->with(['lines' => fn ($query) => $query->orderBy('sequence'), 'lines.jobTitle', 'lines.actedBy'])
            ->latest('started_at')
            ->first()
        : null;

    $lineColor = fn ($status) => match ($status->value) {
        'approved' => 'lime',
        'rejected' => 'red',
        default => 'zinc',
    };
@endphp

@if ($transaction && $transaction->lines->isNotEmpty())
    <div class="flex flex-col gap-3 rounded-[1.75rem] border border-app bg-surface-1 p-5 sm:p-6 shadow-lg shadow-[var(--color-card-shadow)]">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <flux:heading size="sm" level="2">{{ __('Approval Cycle') }}</flux:heading>
            <flux:badge color="{{ match ($transaction->status->value) {
                'approved' => 'lime',
                'rejected' => 'red',
                'cancelled' => 'zinc',
                default => 'amber',
            } }}" size="sm">{{ $transaction->status->label() }}</flux:badge>
        </div>

        <div class="flex flex-wrap items-stretch gap-2">
            @foreach ($transaction->lines as $line)
                <div
                    wire:key="cycle-approval-line-{{ $line->getKey() }}"
                    class="flex min-w-[10rem] flex-1 flex-col gap-1 rounded-2xl border border-app bg-surface-0 p-3"
                >
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-bold text-muted">{{ $line->sequence }}. {{ $line->getTranslation('name', app()->getLocale()) }}</span>
                        <flux:badge color="{{ $lineColor($line->status) }}" size="sm">{{ $line->status->label() }}</flux:badge>
                    </div>

                    @if ($line->jobTitle)
                        <span class="text-xs text-muted">{{ $line->jobTitle->name }}</span>
                    @endif

                    @if ($line->acted_by)
                        <span class="text-xs text-muted">{{ $line->actedBy?->name }} · {{ $line->acted_at?->diffForHumans() }}</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif
