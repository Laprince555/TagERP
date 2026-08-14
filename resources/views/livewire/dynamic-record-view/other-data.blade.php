<div wire:key="other-data-{{ $recordId }}">
    @if ($tabs === [])
        <div class="rounded-lg border border-dashed border-zinc-300 p-6 text-center text-zinc-500 dark:border-zinc-600">
            No sub applications available.
        </div>
    @else
        <div class="flex gap-2 border-b border-zinc-200 dark:border-zinc-700" role="tablist">
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
                        'border-transparent text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200' => $currentTab?->getKey() !== $tab->getKey(),
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
