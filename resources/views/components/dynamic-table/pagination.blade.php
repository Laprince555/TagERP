@props(['paginator'])

{{--
    $paginator may be a LengthAwarePaginator (standard mode: has total()/firstItem()/lastItem())
    or a plain Paginator (simple mode: none of those exist — calling them throws
    BadMethodCallException). Every branch below is gated on the concrete interface,
    never assumes total() is available.
--}}
<div class="mt-4 flex items-center justify-between text-sm text-[var(--color-text-muted)]">
    <span>
        @if ($paginator instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
            {{ __(':from-:to of :total', ['from' => $paginator->firstItem() ?? 0, 'to' => $paginator->lastItem() ?? 0, 'total' => $paginator->total()]) }}
        @elseif ($paginator instanceof \Illuminate\Contracts\Pagination\CursorPaginator)
            {{ __('Cursor Pagination') }}
        @else
            {{ __('Page :page', ['page' => $paginator->currentPage()]) }}
        @endif
    </span>

    <div class="flex items-center gap-2">
        @if ($paginator instanceof \Illuminate\Contracts\Pagination\CursorPaginator)
            <flux:button size="sm" variant="ghost" :disabled="$paginator->onFirstPage()" wire:click="$set('cursor', '{{ $paginator->previousCursor()?->encode() }}')">
                {{ __('Previous') }}
            </flux:button>
            <flux:button size="sm" variant="ghost" :disabled="! $paginator->hasMorePages()" wire:click="$set('cursor', '{{ $paginator->nextCursor()?->encode() }}')">
                {{ __('Next') }}
            </flux:button>
        @else
            <flux:button size="sm" variant="ghost" :disabled="! $paginator->previousPageUrl()" wire:click="gotoPage({{ max(1, $paginator->currentPage() - 1) }})">
                {{ __('Previous') }}
            </flux:button>
            <flux:button size="sm" variant="ghost" :disabled="! $paginator->hasMorePages()" wire:click="gotoPage({{ $paginator->currentPage() + 1 }})">
                {{ __('Next') }}
            </flux:button>
        @endif
    </div>
</div>
