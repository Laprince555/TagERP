@props(['title', 'subtitle' => null, 'code' => null])

<div class="flex flex-col gap-1 border-b border-zinc-200 pb-4 dark:border-zinc-700">
    <div class="flex items-center gap-3">
        <flux:heading size="xl">{{ $title }}</flux:heading>

        @if ($code)
            <flux:badge color="zinc" size="sm">{{ $code }}</flux:badge>
        @endif
    </div>

    @if ($subtitle)
        <flux:text class="text-zinc-500 dark:text-zinc-400">{{ $subtitle }}</flux:text>
    @endif

    {{ $slot ?? '' }}
</div>
