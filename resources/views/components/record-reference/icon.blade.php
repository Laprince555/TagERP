@props(['identity'])
@php
    /** @var \App\Support\RecordReference\RecordReferenceIdentity $identity */
    use App\Support\RecordReference\RecordReferencePalette;
    $tokens = RecordReferencePalette::resolve($identity->applicationColor);
@endphp
@if (! $identity->url)
    <span
        aria-label="{{ $identity->title }} ({{ $identity->applicationName }})"
        {{ $attributes->class(['inline-flex size-8 items-center justify-center rounded-full border', $tokens['bg'], $tokens['text'], $tokens['border'], $tokens['darkBg'], $tokens['darkText'], $tokens['ring']]) }}
    >
        @if ($identity->applicationIcon)
            <flux:icon :icon="$identity->applicationIcon" variant="micro" />
        @endif
    </span>
@else
<span
    x-data="{
        hoverTimer: null,
        openPreview() {
            const rect = $el.getBoundingClientRect();
            window.dispatchEvent(new CustomEvent('record-reference:open-preview', {
                detail: { code: @js($identity->applicationCode), key: @js($identity->recordKey), x: rect.left, y: rect.bottom, anchor: $el },
            }));
        },
        scheduleOpen() { this.hoverTimer = setTimeout(() => this.openPreview(), 400); },
        cancelOpen() { clearTimeout(this.hoverTimer); }
    }"
    x-on:mouseenter="scheduleOpen()"
    x-on:mouseleave="cancelOpen()"
    x-on:focus="openPreview()"
    class="inline-flex items-center gap-1"
>
    <a
        href="{{ $identity->url }}"
        class="inline-flex min-w-[44px] min-h-[44px] items-center justify-center focus:outline-none"
        aria-label="{{ $identity->title }} ({{ $identity->applicationName }})"
    >
        <span {{ $attributes->class(['inline-flex size-8 items-center justify-center rounded-full border', $tokens['bg'], $tokens['text'], $tokens['border'], $tokens['darkBg'], $tokens['darkText'], $tokens['ring']]) }}>
            @if ($identity->applicationIcon)
                <flux:icon :icon="$identity->applicationIcon" variant="micro" />
            @endif
        </span>
    </a>
    <button
        type="button"
        class="inline-flex min-w-[44px] min-h-[44px] items-center justify-center sm:hidden focus:outline-none"
        aria-label="{{ __('messages.record_reference.open_preview') }}"
        x-on:click.stop.prevent="openPreview()"
    >
        <span class="inline-flex size-8 items-center justify-center rounded-full border border-app bg-primary-soft hover:bg-primary/30">
            <flux:icon icon="eye" variant="micro" />
        </span>
    </button>
</span>
@endif
