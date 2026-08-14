@props(['identity', 'facts' => []])
@php
    /** @var \App\Support\RecordReference\RecordReferenceIdentity $identity */
    /** @var \App\Support\RecordReference\RecordFact[] $facts */
    use App\Support\RecordReference\RecordReferencePalette;
    $tokens = RecordReferencePalette::resolve($identity->applicationColor);
    $sortedFacts = collect($facts)->sortByDesc('priority')->take(4);
    $tag = $identity->url ? 'a' : 'div';
@endphp
<{{ $tag }}
    @if ($identity->url) href="{{ $identity->url }}" @endif
    {{ $attributes->class(['flex flex-col gap-2 rounded-lg border p-3 transition', $identity->url ? 'hover:shadow-sm' : '', $tokens['border'], $tokens['bg'], $tokens['darkBg']]) }}
>
    <div class="flex items-center gap-2">
        <span class="inline-flex size-8 items-center justify-center rounded-md {{ $tokens['bg'] }} {{ $tokens['darkBg'] }} {{ $tokens['text'] }} {{ $tokens['darkText'] }}">
            @if ($identity->applicationIcon)
                <flux:icon :icon="$identity->applicationIcon" variant="micro" />
            @endif
        </span>
        <span class="text-xs font-medium {{ $tokens['text'] }} {{ $tokens['darkText'] }}">{{ $identity->applicationName }}</span>
    </div>

    <span class="text-sm font-semibold text-[var(--color-content-primary,inherit)]">{{ $identity->title }}</span>

    @if ($sortedFacts->isNotEmpty())
        <dl class="grid grid-cols-2 gap-x-3 gap-y-1 text-xs">
            @foreach ($sortedFacts as $fact)
                <div wire:key="card-fact-{{ $identity->recordKey }}-{{ $loop->index }}">
                    <dt class="text-[var(--color-content-tertiary,#6b7280)]">{{ $fact->label }}</dt>
                    <dd class="font-medium">{{ $fact->value }}</dd>
                </div>
            @endforeach
        </dl>
    @endif
</{{ $tag }}>
