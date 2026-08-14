<div wire:key="dynamic-table-{{ $definition->tableKey }}">
    <x-dynamic-table.toolbar :table-key="$definition->tableKey" :per-page="$state->perPage" />

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
            <x-dynamic-table.table
                :columns="$definition->authorizedColumns()"
                :visible-columns="$orderedVisibleColumns"
                :rows="$rows"
                :sorts="$state->sorts"
                :reference-applications="$referenceApplications"
                :relationship-actions="$relationshipActions"
            />

            <x-dynamic-table.pagination :paginator="$rows" />
        @endif
    </div>
</div>
