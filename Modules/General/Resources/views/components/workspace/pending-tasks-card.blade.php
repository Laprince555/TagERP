@props([
    'hint' => null,
])

{{-- Visual placeholder for a future task system. No task data is loaded or implied. --}}
<div {{ $attributes->class(['rounded-[1.5rem] border border-[var(--color-glass-border)] bg-[var(--color-surface-1)] p-5 shadow-lg shadow-[var(--color-card-shadow)]']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="flex min-w-0 items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-[linear-gradient(135deg,var(--color-primary-soft),var(--color-accent-soft))] text-[var(--color-primary)]">
                <flux:icon name="clipboard-document-check" class="h-5 w-5" />
            </div>

            <div class="min-w-0">
                <flux:heading size="sm" class="truncate">{{ __('messages.workspace.pending_tasks') }}</flux:heading>
                <p class="truncate text-xs text-[var(--color-muted-text)]">{{ $hint ?? __('messages.workspace.pending_tasks_hint') }}</p>
            </div>
        </div>

        <flux:badge color="zinc" size="sm">{{ __('messages.workspace.coming_soon') }}</flux:badge>
    </div>

    <flux:skeleton.group animate="pulse" class="mt-4 space-y-3" aria-hidden="true">
        @foreach (range(1, 3) as $placeholderRow)
            <div wire:key="pending-task-placeholder-{{ $placeholderRow }}" class="flex items-center gap-3">
                <flux:skeleton class="h-8 w-8 rounded-xl" />

                <div class="flex-1 space-y-2">
                    <flux:skeleton class="h-2.5 w-1/2 rounded-full" />
                    <flux:skeleton class="h-2.5 w-3/4 rounded-full" />
                </div>
            </div>
        @endforeach
    </flux:skeleton.group>

    <div class="mt-4 flex items-center justify-between border-t border-[var(--color-glass-border)] pt-3">
        <span class="text-xs font-bold uppercase tracking-[0.18em] text-[var(--color-muted-text)]">{{ __('messages.workspace.open_tasks') }}</span>
        <span class="text-lg font-black text-[var(--color-text-main)]">—</span>
    </div>

    {{-- Disabled until a real task system exists; swap `disabled` for an href then. --}}
    <flux:button
        type="button"
        variant="ghost"
        size="sm"
        icon:trailing="arrow-right"
        disabled
        class="mt-3 w-full rtl:[&_svg]:rotate-180"
    >
        {{ __('messages.workspace.view_all_tasks') }}
    </flux:button>
</div>
