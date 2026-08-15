<div
    class="relative"
    x-data="{ open: false }"
    x-on:keydown.escape.window="open = false"
    wire:poll.30s
>
    <button
        type="button"
        x-on:click="open = ! open"
        aria-label="{{ __('Notifications') }}"
        @class([
            'relative flex h-10 w-10 items-center justify-center rounded-2xl border transition',
            'border-[var(--color-primary)] bg-[var(--color-primary-soft)] text-[var(--color-primary)] shadow-lg shadow-[var(--color-primary)]/25' => $unreadCount > 0,
            'border-[var(--color-border)] bg-[var(--color-card-bg)] text-[var(--color-text-main)]/70 hover:border-[var(--color-primary)]' => $unreadCount === 0,
        ])
    >
        <flux:icon.bell variant="{{ $unreadCount > 0 ? 'solid' : 'outline' }}" class="size-5" />

        @if ($unreadCount > 0)
            <span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-[var(--color-primary)] px-1 text-[10px] font-black text-white ring-2 ring-[var(--color-card-bg)]">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
            <span class="absolute inset-0 animate-ping rounded-2xl border border-[var(--color-primary)]/40"></span>
        @endif
    </button>

    <div
        x-cloak
        x-show="open"
        x-on:click.outside="open = false"
        x-transition.origin.top.duration.150ms
        class="absolute end-0 z-50 mt-2 w-[22rem] overflow-hidden rounded-3xl border border-[var(--color-border)] bg-[var(--color-card-bg)] shadow-2xl shadow-black/20 sm:w-[26rem]"
    >
        <div class="flex items-center justify-between gap-3 border-b border-[var(--color-border)] px-4 py-3">
            <div class="min-w-0">
                <p class="truncate text-sm font-black tracking-wide">{{ __('Notifications') }}</p>
                <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-[var(--color-text-main)]/50">
                    {{ $unreadCount > 0 ? __(':count unread', ['count' => $unreadCount]) : __('All caught up') }}
                </p>
            </div>

            @if ($unreadCount > 0)
                <flux:button size="sm" variant="ghost" wire:click="markAllRead" class="shrink-0">
                    {{ __('Mark all read') }}
                </flux:button>
            @endif
        </div>

        <div class="max-h-[26rem] overflow-y-auto">
            @forelse ($notifications as $notification)
                @php
                    $data = $notification->data;
                    $isUnread = $notification->read_at === null;
                @endphp

                <div
                    wire:key="notification-{{ $notification->id }}"
                    @class([
                        'group flex items-start gap-3 border-b border-[var(--color-border)]/60 px-4 py-3 last:border-b-0 transition',
                        'bg-[var(--color-primary-soft)]/40' => $isUnread,
                        'hover:bg-[var(--color-canvas-bg)]' => ! $isUnread,
                    ])
                >
                    @php($isFailure = ($data['type'] ?? null) === 'export-failed')

                    <span @class([
                        'mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl',
                        'bg-red-500 text-white' => $isFailure,
                        'bg-[var(--color-primary)] text-white' => $isUnread && ! $isFailure,
                        'bg-[var(--color-canvas-bg)] text-[var(--color-text-main)]/60' => ! $isUnread && ! $isFailure,
                    ])>
                        <flux:icon :icon="$data['icon'] ?? 'bell'" class="size-4" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold">{{ $data['title'] ?? __('Notification') }}</p>
                        <p class="mt-0.5 break-words text-xs text-[var(--color-text-main)]/65">{{ $data['body'] ?? '' }}</p>

                        <div class="mt-2 flex items-center gap-3">
                            <span class="text-[11px] font-medium uppercase tracking-[0.18em] text-[var(--color-text-main)]/45">
                                {{ $notification->created_at->diffForHumans() }}
                            </span>

                            @if (($data['type'] ?? null) === 'export')
                                <flux:link
                                    href="{{ route('exports.download', $notification->id) }}"
                                    class="text-xs font-bold text-[var(--color-primary)]"
                                >
                                    {{ __('Download') }}
                                </flux:link>
                            @endif

                            @if ($isUnread)
                                <button type="button" wire:click="markRead('{{ $notification->id }}')" class="text-xs font-medium text-[var(--color-text-main)]/55 hover:text-[var(--color-primary)]">
                                    {{ __('Mark read') }}
                                </button>
                            @endif

                            <button type="button" wire:click="delete('{{ $notification->id }}')" class="ms-auto text-[var(--color-text-main)]/35 opacity-0 transition group-hover:opacity-100 hover:text-red-500">
                                <flux:icon.x-mark class="size-4" />
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center gap-2 px-6 py-12 text-center">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[var(--color-canvas-bg)] text-[var(--color-text-main)]/40">
                        <flux:icon.bell-slash class="size-5" />
                    </span>
                    <p class="text-sm font-bold">{{ __('No notifications yet') }}</p>
                    <p class="text-xs text-[var(--color-text-main)]/55">{{ __('Queued exports and system alerts will land here.') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
