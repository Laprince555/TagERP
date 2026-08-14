<?php

namespace App\Livewire\DynamicRecordView;

use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\RecordSection;
use App\Support\DynamicRecordView\Core\RecordViewRegistry;
use App\Support\DynamicRecordView\Resolution\RecordResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Independent Livewire island for the Other Data section. It carries its own
 * activeTab state, entirely separate from RecordView's primary-tab state —
 * being a distinct component instance, switching one never triggers a
 * request, remount, or re-query for the other.
 *
 * Keyed by recordId in the parent Blade (see record-view.blade.php): the
 * same key across a same-record update preserves this component's state;
 * navigating to a different record changes the key, so Livewire tears down
 * and remounts this component fresh — resetting activeTab as required.
 *
 * Only the active tab's TableContent is rendered, so switching tabs here
 * never queries the tables behind inactive tabs.
 *
 * $recordViewKey is a RecordViewRegistry key, never a raw class name — the
 * browser can never pick an arbitrary class to instantiate. The parent
 * record is re-resolved through RecordResolver's authorized query() on
 * mount and on every action that needs it, so a parent deleted or made
 * unauthorized between mount and a later request fails the same safe 404
 * way RecordView's primary component does — authorization callbacks receive
 * the actual resolved model, never a bare id.
 */
class OtherData extends Component
{
    #[Locked]
    public string $recordViewKey;

    #[Locked]
    public int|string $recordId;

    public string $activeTab = '';

    public function mount(string $recordViewKey, int|string $recordId): void
    {
        $this->recordViewKey = $recordViewKey;
        $this->recordId = $recordId;

        $record = $this->resolveRecord();
        $tabs = $this->section()->authorizedTabs($record);

        if ($tabs !== []) {
            $this->activeTab = $this->section()->defaultTabKey($record);
        }
    }

    public function hydrate(): void
    {
        $record = $this->resolveRecord();
        $this->activeTab = $this->section()->normalizeActiveTabKey($this->activeTab, $record);
    }

    public function setActiveTab(string $key): void
    {
        $record = app(RecordResolver::class)->resolveFresh($this->definition(), $this->recordId);
        $this->activeTab = $this->section()->normalizeActiveTabKey($key, $record);
    }

    protected function definition(): DynamicRecordView
    {
        $class = app(RecordViewRegistry::class)->resolve($this->recordViewKey);

        return app($class);
    }

    protected function section(): RecordSection
    {
        return $this->definition()->otherDataSection();
    }

    /**
     * Re-resolved (not cached on the component) on every call so a parent
     * deleted or made unauthorized after mount() fails safely on the next
     * request too — RecordResolver itself memoizes per-request only.
     */
    protected function resolveRecord(): Model
    {
        return app(RecordResolver::class)->resolve($this->definition(), $this->recordId);
    }

    public function render(): View
    {
        $record = $this->resolveRecord();
        $section = $this->section();
        $tabs = $section->authorizedTabs($record);
        $currentTab = collect($tabs)->first(fn ($tab) => $tab->getKey() === $this->activeTab);

        // Self-heal: a forged/stale activeTab never renders unauthorized content
        // (currentTab stays null until corrected), but we also fix the public
        // property so the tab bar doesn't render with nothing selected.
        if ($currentTab === null && $tabs !== []) {
            $this->activeTab = $section->normalizeActiveTabKey($this->activeTab, $record);
            $currentTab = collect($tabs)->first(fn ($tab) => $tab->getKey() === $this->activeTab);
        }

        return view('livewire.dynamic-record-view.other-data', [
            'tabs' => $tabs,
            'currentTab' => $currentTab,
            'record' => $record,
            'recordId' => $this->recordId,
        ]);
    }
}
