<div wire:key="dynamic-table-{{ $definition->tableKey }}">
    <x-dynamic-table.toolbar
        :table-key="$definition->tableKey"
        :per-page="$state->perPage"
        :create-form-key="$createFormKey"
        :create-form-label="$createFormLabel"
    />

    @if ($createFormKey)
        <livewire:dynamic-form.form-modal
            :form-key="$createFormKey"
            :heading="$createFormLabel"
            :key="'form-modal-'.$createFormKey"
        />
    @endif

    @if ($relationshipActions?->isLinkable())
        <div class="mb-2 flex justify-end">
            <flux:button
                size="sm"
                variant="primary"
                wire:click="$dispatch('open-relation-picker.{{ $instanceIdentifier }}')"
            >
                {{ __('Link') }}
            </flux:button>
        </div>
    @endif

    @auth
        <x-dynamic-table.saved-views
            :views="$this->savedViews"
            :active-view-id="$this->activeViewId"
            :new-view-name="$this->newViewName"
            :save-view-error="$this->saveViewError"
        />
    @endauth

    <x-dynamic-table.filter-panel
        :filters="$definition->authorizedFilters()"
        :draft="$filters"
        :belongs-to-selected-labels="$belongsToSelectedLabels"
        :belongs-to-options="$this->belongsToOptions"
        :belongs-to-search="$this->belongsToSearch"
        :active-filter-chips="$activeFilterChips"
        :has-draft-filter-changes="$hasDraftFilterChanges"
    />

    <x-dynamic-table.column-manager
        :columns="$definition->authorizedColumns()"
        :visible="$state->visibleColumns"
        :column-order="$state->columnOrder"
    />

    <div wire:loading.flex class="hidden">
        <x-dynamic-table.loading-state />
    </div>

    <div wire:loading.remove>
        @if ($rows->isEmpty())
            <x-dynamic-table.empty-state />
        @else
            @if (count($this->selectedIds) > 0 && $rows->total() > count($this->selectedIds))
                <div class="mb-2 rounded-md bg-[var(--color-accent)]/10 px-3 py-2 text-sm text-[var(--color-accent)]">
                    @if ($this->selectAllMatching)
                        {{ __('All :count matching records are selected.', ['count' => $rows->total()]) }}
                        <button type="button" wire:click="toggleSelectAllMatching" class="ms-1 underline">{{ __('Select this page only') }}</button>
                    @else
                        {{ __(':count selected on this page.', ['count' => count($this->selectedIds)]) }}
                        <button type="button" wire:click="toggleSelectAllMatching" class="ms-1 underline">
                            {{ __('Select all :count matching records', ['count' => $rows->total()]) }}
                        </button>
                    @endif
                </div>
            @endif

            <x-dynamic-table.table
                :columns="$definition->authorizedColumns()"
                :visible-columns="$orderedVisibleColumns"
                :rows="$rows"
                :sorts="$state->sorts"
                :reference-applications="$referenceApplications"
                :reference-cells="$referenceCells"
                :relationship-actions="$relationshipActions"
                :selectable="true"
                :selected-ids="$this->selectedIds"
            />

            <x-dynamic-table.pagination :paginator="$rows" />
        @endif
    </div>
</div>
