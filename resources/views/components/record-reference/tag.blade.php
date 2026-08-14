@props(['identity'])
@php
    /** @var \App\Support\RecordReference\RecordReferenceIdentity $identity */
    use App\Support\RecordReference\RecordReferencePalette;
    $tokens = RecordReferencePalette::resolve($identity->applicationColor);
@endphp
@if (! $identity->url)
    <span
        aria-label="{{ $identity->title }} ({{ $identity->applicationName }})"
        {{ $attributes->class(['inline-flex max-w-full items-center gap-1.5 rounded-full border px-2 py-0.5 text-xs font-medium', $tokens['bg'], $tokens['text'], $tokens['border'], $tokens['darkBg'], $tokens['darkText']]) }}
    >
        @if ($identity->applicationIcon)
            <flux:icon :icon="$identity->applicationIcon" variant="micro" />
        @endif
        <span class="truncate">{{ $identity->title }}</span>
    </span>
@else
<span
    x-data="{
        openPreview(e) {
            e.preventDefault();
            const rect = $el.getBoundingClientRect();
            window.dispatchEvent(new CustomEvent('record-reference:open-preview', {
                detail: { code: @js($identity->applicationCode), key: @js($identity->recordKey), x: rect.left, y: rect.bottom, anchor: $el },
            }));
        }
    }"
    x-on:contextmenu="openPreview($event)"
    x-on:keydown.shift.f10.prevent="openPreview($event)"
    x-on:keydown.context-menu.prevent="openPreview($event)"
    title="{{ __('messages.record_reference.context_menu_instructions') }}"
    {{ $attributes->class(['inline-flex max-w-full items-center gap-1 rounded-full border px-2 py-0.5 text-xs font-medium', $tokens['bg'], $tokens['text'], $tokens['border'], $tokens['darkBg'], $tokens['darkText']]) }}
>
    <a
        href="{{ $identity->url }}"
        class="inline-flex min-w-0 items-center gap-1.5 hover:underline"
        aria-label="{{ $identity->title }} ({{ $identity->applicationName }})"
    >
        @if ($identity->applicationIcon)
            <flux:icon :icon="$identity->applicationIcon" variant="micro" />
        @endif
        <span class="truncate">{{ $identity->title }}</span>
    </a>
    <button
        type="button"
        class="ms-0.5 inline-flex min-w-[44px] min-h-[44px] items-center justify-center rounded-full opacity-60 hover:opacity-100 sm:hidden focus:outline-none"
        aria-label="{{ __('messages.record_reference.open_preview') }}"
        x-on:click.stop.prevent="openPreview($event)"
    >
        <span class="inline-flex size-6 items-center justify-center rounded-full bg-black/5 hover:bg-black/10 dark:bg-white/5 dark:hover:bg-white/10">
            <flux:icon icon="ellipsis-horizontal" variant="micro" />
        </span>
    </button>
</span>
@endif
