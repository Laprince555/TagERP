<div wire:key="record-view-{{ $definition->getViewKey() }}-{{ $record->getKey() }}">
    <x-dynamic-record-view.header
        :title="$definition->title($record)"
        :subtitle="$definition->subtitle($record)"
        :code="$record->code ?? null"
    />

    <div class="mt-4 flex gap-2 border-b border-zinc-200 dark:border-zinc-700" role="tablist">
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
                    'px-3 py-2 text-sm font-medium border-b-2 -mb-px',
                    'border-[var(--color-accent)] text-[var(--color-accent)]' => $currentTab?->getKey() === $tab->getKey(),
                    'border-transparent text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200' => $currentTab?->getKey() !== $tab->getKey(),
                ])
            >
                {{ $tab->getLabel() }}
            </button>
        @endforeach
    </div>

    <div class="mt-4 flex flex-col gap-4">
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
        <div class="mt-8">
            <flux:heading size="lg" class="mb-3">{{ $definition->otherDataSection()->getLabel() }}</flux:heading>

            @livewire(
                \App\Livewire\DynamicRecordView\OtherData::class,
                ['recordViewKey' => $definition->getViewKey(), 'recordId' => $record->getKey()],
                key('other-data-'.$definition->getViewKey().'-'.$record->getKey())
            )
        </div>
    @endif
</div>
