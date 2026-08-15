<div
    wire:key="record-view-{{ $definition->getViewKey() }}-{{ $record->getKey() }}"
    class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 space-y-6"
>
    <div class="flex items-center gap-2">
        <livewire:general.breadcrumbs :trailing="$definition->title($record)" />
    </div>

    <x-dynamic-record-view.header
        :title="$definition->title($record)"
        :subtitle="$definition->subtitle($record)"
        :code="$record->code ?? null"
    />

    <div class="space-y-4">
        <div class="flex gap-2 border-b border-[var(--color-glass-border)]" role="tablist">
            @foreach ($tabs as $tab)
                <button
                    type="button"
                    id="primary-tab-{{ $tab->getKey() }}"
                    wire:key="primary-tab-{{ $tab->getKey() }}"
                    wire:click="setActiveTab('{{ $tab->getKey() }}')"
                    role="tab"
                    aria-selected="{{ $currentTab?->getKey() === $tab->getKey() ? 'true' : 'false' }}"
                    aria-controls="primary-tabpanel-{{ $tab->getKey() }}"
                    @class([
                        'px-4 py-2.5 text-sm font-bold border-b-2 -mb-px transition duration-200',
                        'border-[var(--color-primary)] text-[var(--color-primary)]' => $currentTab?->getKey() === $tab->getKey(),
                        'border-transparent text-[var(--color-soft-text)] hover:text-[var(--color-text-main)]' => $currentTab?->getKey() !== $tab->getKey(),
                    ])
                >
                    {{ $tab->getLabel() }}
                </button>
            @endforeach
        </div>

        @if ($currentTab)
            <div role="tabpanel" id="primary-tabpanel-{{ $currentTab->getKey() }}" aria-labelledby="primary-tab-{{ $currentTab->getKey() }}">
                @foreach ($currentTab->getContents() as $content)
                    <x-dynamic-record-view.content
                        :content="$content"
                        :record="$record"
                        :record-view-key="$definition->getViewKey()"
                        section="primary"
                        :tab="$currentTab->getKey()"
                        :reference-fields="$referenceFields"
                    />
                @endforeach
            </div>
        @endif
    </div>

    @if ($definition->subApplications() !== [])
        <div class="pt-6 border-t border-[var(--color-glass-border)] space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="lg" level="2">{{ $definition->otherDataSection()->getLabel() }}</flux:heading>
                </div>
            </div>

            @livewire(
                \App\Livewire\DynamicRecordView\OtherData::class,
                ['recordViewKey' => $definition->getViewKey(), 'recordId' => $record->getKey()],
                key('other-data-'.$definition->getViewKey().'-'.$record->getKey())
            )
        </div>
    @endif
</div>
