@props(['content', 'record', 'recordViewKey', 'section', 'tab', 'referenceFields' => []])

{{--
    Shared content-block renderer for every tab in a Dynamic Record View —
    primary/top tabs AND Other Data/sub-application tabs alike. Tabs are
    generic containers: any authorized, visible Content subclass can appear
    in either place, so this is the single instanceof-branching point instead
    of duplicating it per section's Blade view.

    An embedded TableContent never receives the parent record directly —
    only bounded scalar identifiers (recordViewKey/record id/section/tab/
    content key). The Table component re-resolves the relation constraint
    fresh via EmbeddedTableContext on every request (see
    App\Livewire\DynamicTable\Table::resolvedQuery()).
--}}
@if ($content->isVisible($record))
    @if ($content instanceof \App\Support\DynamicRecordView\Core\Content\FieldsContent)
        <x-dynamic-record-view.fields-content :content="$content" :record="$record" :reference-fields="$referenceFields" wire:key="content-{{ $section }}-{{ $tab }}-{{ $content->getKey() }}" />
    @elseif ($content instanceof \App\Support\DynamicRecordView\Core\Content\EmptyStateContent)
        <div class="rounded-lg border border-dashed border-app p-8 text-center text-muted" wire:key="content-{{ $section }}-{{ $tab }}-{{ $content->getKey() }}">
            {{ $content->getMessage() }}
        </div>
    @elseif ($content instanceof \App\Support\DynamicRecordView\Core\Content\TableContent && $content->getTable())
        @livewire(
            $content->getTable(),
            [
                'embedRecordViewKey' => $recordViewKey,
                'embedRecordId' => $record->getKey(),
                'embedSection' => $section,
                'embedTab' => $tab,
                'embedContent' => $content->getKey(),
            ],
            key('embedded-table-'.$recordViewKey.'-'.$section.'-'.$tab.'-'.$content->getKey().'-'.$record->getKey())
        )

        @if ($content->getRelationshipActions()?->isLinkable())
            @livewire(
                \App\Livewire\DynamicRecordView\RelationPickerModal::class,
                [
                    'recordViewKey' => $recordViewKey,
                    'recordId' => $record->getKey(),
                    'section' => $section,
                    'tab' => $tab,
                    'contentKey' => $content->getKey(),
                    'tableClass' => $content->getTable(),
                ],
                key('relation-picker-'.$recordViewKey.'-'.$section.'-'.$tab.'-'.$content->getKey().'-'.$record->getKey())
            )
        @endif
    @endif
@endif
