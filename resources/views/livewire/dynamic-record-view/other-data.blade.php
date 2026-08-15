<div wire:key="other-data-{{ $recordId }}">
    @if ($tabs === [])
        <div class="rounded-lg border border-dashed border-app p-6 text-center text-muted">
            No sub applications available.
        </div>
    @else
        <div class="flex gap-2 border-b border-app" role="tablist">
            @foreach ($tabs as $tab)
                <button
                    type="button"
                    id="other-data-tab-{{ $tab->getKey() }}"
                    wire:key="other-data-tab-{{ $tab->getKey() }}"
                    wire:click="setActiveTab('{{ $tab->getKey() }}')"
                    role="tab"
                    aria-selected="{{ $currentTab?->getKey() === $tab->getKey() ? 'true' : 'false' }}"
                    aria-controls="other-data-tabpanel-{{ $tab->getKey() }}"
                    @class([
                        'px-3 py-2 text-sm font-medium border-b-2 -mb-px',
                        'border-[var(--color-accent)] text-[var(--color-accent)]' => $currentTab?->getKey() === $tab->getKey(),
                        'border-transparent text-muted hover:text-main' => $currentTab?->getKey() !== $tab->getKey(),
                    ])
                >
                    {{ $tab->getLabel() }}
                </button>
            @endforeach
        </div>

        <div class="mt-4">
            @if ($currentTab)
                <div role="tabpanel" id="other-data-tabpanel-{{ $currentTab->getKey() }}" aria-labelledby="other-data-tab-{{ $currentTab->getKey() }}">
                    @foreach ($currentTab->getContents() as $content)
                        <x-dynamic-record-view.content
                            :content="$content"
                            :record="$record"
                            :record-view-key="$recordViewKey"
                            section="other-data"
                            :tab="$currentTab->getKey()"
                        />
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>
