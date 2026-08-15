@props(['content', 'record', 'referenceFields' => []])

<div class="rounded-[1.5rem] border border-[var(--color-glass-border)] bg-[var(--color-surface-1)] p-5 sm:p-6 shadow-lg shadow-[var(--color-card-shadow)] space-y-4">
    @if ($content->getHeading())
        <flux:heading size="lg" class="mb-4">{{ $content->getHeading() }}</flux:heading>
    @endif

    <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($content->getFields() as $field)
            @continue(! $field->isVisible($record))

            @if ($field instanceof \App\Support\DynamicRecordView\Core\Fields\RecordReferenceViewField)
                @php($ref = $referenceFields[$field->getKey()] ?? null)
                <div class="flex flex-col gap-1.5" style="grid-column: span {{ $field->getColumnSpan() }} / span {{ $field->getColumnSpan() }}" wire:key="field-{{ $field->getKey() }}">
                    <dt class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-[0.14em] text-[var(--color-muted-text)]">
                        {{ $field->getLabel() }}
                    </dt>
                    <dd class="flex items-center gap-1.5 text-sm text-[var(--color-text-main)]">
                        @if ($ref && $ref['variant'] === 'card')
                            <x-record-reference.card :identity="$ref['identity']" :facts="$ref['facts']" />
                        @elseif ($ref && $ref['variant'] === 'tag')
                            <x-record-reference.tag :identity="$ref['identity']" />
                        @elseif ($ref)
                            <x-record-reference.icon :identity="$ref['identity']" />
                        @else
                            <span class="text-[var(--color-soft-text)]">{{ $field->getPlaceholder() }}</span>
                        @endif
                    </dd>
                </div>
            @else
                <div class="flex flex-col gap-1.5" style="grid-column: span {{ $field->getColumnSpan() }} / span {{ $field->getColumnSpan() }}" wire:key="field-{{ $field->getKey() }}" @if ($field->getTooltip()) title="{{ $field->getTooltip() }}" @endif>
                    <dt class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-[0.14em] text-[var(--color-muted-text)]">
                        @if ($field->getIcon())
                            <flux:icon :name="$field->getIcon()" class="size-3.5 text-[var(--color-muted-text)]" />
                        @endif
                        {{ $field->getLabel() }}
                    </dt>

                    @php($value = $field->display($record))

                    <dd class="flex items-center gap-1.5 text-sm text-[var(--color-text-main)]">
                        @if ($value === null || $value === '')
                            <span class="text-[var(--color-soft-text)]">{{ $field->getPlaceholder() }}</span>
                        @elseif ($field->getBadge($value, $record))
                            <flux:badge size="sm" :color="$field->getColor($value, $record) ?? 'zinc'">{{ $field->getBadge($value, $record) }}</flux:badge>
                        @elseif (method_exists($field, 'getUrl') && $field->getUrl($record))
                            <flux:link :href="$field->getUrl($record)" :target="$field->opensInNewTab() ? '_blank' : null">{{ $value }}</flux:link>
                        @else
                            {{ $value }}
                        @endif

                        @if ($field->isCopyable() && $value !== null && $value !== '')
                            <button
                                type="button"
                                x-data
                                x-on:click="navigator.clipboard.writeText('{{ addslashes((string) $value) }}')"
                                class="text-[var(--color-soft-text)] hover:text-[var(--color-primary)] transition"
                                aria-label="{{ __('Copy :label', ['label' => $field->getLabel()]) }}"
                            >
                                <flux:icon name="clipboard" class="size-3.5" />
                            </button>
                        @endif
                    </dd>
                </div>
            @endif
        @endforeach
    </dl>
</div>
