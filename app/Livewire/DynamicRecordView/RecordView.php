<?php

namespace App\Livewire\DynamicRecordView;

use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\RecordReferenceViewField;
use App\Support\DynamicRecordView\Core\RecordSection;
use App\Support\DynamicRecordView\Core\RecordViewRegistry;
use App\Support\DynamicRecordView\Resolution\RecordResolver;
use App\Support\RecordReference\RecordReferenceAccess;
use App\Support\RecordReference\RecordReferenceRegistry;
use App\Support\RecordReference\RecordReferenceResolver;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Renders the Primary section (header + Basic Information tabs) for one
 * Dynamic Record View. The Other Data section is a separate nested Livewire
 * component (see OtherData) so its tab state never remounts or re-queries
 * when the primary tab changes, and vice versa.
 *
 * One concrete subclass per record type; point recordViewKey() at the
 * matching key registered in App\Support\DynamicRecordView\Core\RecordViewRegistry
 * — never at a raw class name, so the definition actually rendered is always
 * a trusted server-side registry lookup, never client-influenced.
 */
abstract class RecordView extends Component
{
    /**
     * Resolved server-side from the route parameter during mount, never
     * trusted from client input again after that (Locked).
     */
    #[Locked]
    public int|string $recordId;

    public string $activeTab = '';

    /**
     * The stable RecordViewRegistry key for this record type.
     */
    abstract protected function recordViewKey(): string;

    public function mount(int|string $recordId): void
    {
        $this->recordId = $recordId;

        $record = $this->resolveRecord();
        $this->activeTab = $this->primarySection()->defaultTabKey($record);
    }

    public function hydrate(): void
    {
        $record = app(RecordResolver::class)->resolveFresh($this->definition(), $this->recordId);
        $this->activeTab = $this->primarySection()->normalizeActiveTabKey($this->activeTab, $record);
    }

    public function setActiveTab(string $key): void
    {
        $record = app(RecordResolver::class)->resolveFresh($this->definition(), $this->recordId);
        $this->activeTab = $this->primarySection()->normalizeActiveTabKey($key, $record);
    }

    protected function definition(): DynamicRecordView
    {
        $class = app(RecordViewRegistry::class)->resolve($this->recordViewKey());

        return app($class);
    }

    protected function primarySection(): RecordSection
    {
        return $this->definition()->primarySection();
    }

    /**
     * Resolved only through the definition's own authorized query() and
     * memoized per request by RecordResolver — never queried twice per
     * request no matter how many times mount()/render() call this. Fails
     * safely (404) if the record is missing, deleted, or unauthorized.
     */
    protected function resolveRecord(): Model
    {
        return app(RecordResolver::class)->resolve($this->definition(), $this->recordId);
    }

    public function render(): View
    {
        $definition = $this->definition();
        $record = $this->resolveRecord();
        $section = $definition->primarySection();
        $tabs = $section->authorizedTabs($record);
        $currentTab = collect($tabs)->first(fn ($tab) => $tab->getKey() === $this->activeTab);

        // Self-heal: a forged/stale activeTab (e.g. direct Livewire::test()->set())
        // never renders unauthorized content, but here we also correct the public
        // property itself so the tab bar doesn't render with none selected.
        if ($currentTab === null && $tabs !== []) {
            $this->activeTab = $section->normalizeActiveTabKey($this->activeTab, $record);
            $currentTab = collect($tabs)->first(fn ($tab) => $tab->getKey() === $this->activeTab);
        }

        // Gather all RecordReferenceViewFields across the authorized tabs
        $referenceFields = [];
        $referenceCodes = [];
        foreach ($tabs as $tab) {
            foreach ($tab->getContents() as $content) {
                if ($content instanceof FieldsContent) {
                    foreach ($content->getFields() as $field) {
                        if ($field instanceof RecordReferenceViewField && $field->isVisible($record)) {
                            $referenceCodes[] = $field->getApplicationCode();
                        }
                    }
                }
            }
        }

        $referenceCodes = array_values(array_unique(array_filter($referenceCodes)));
        $access = app(RecordReferenceAccess::class);
        $referenceApplications = $access->applications($referenceCodes);

        $applicationAccessibleByCode = $referenceApplications->mapWithKeys(
            fn ($app, $code) => [$code => $access->applicationAccessible($app)],
        );

        $registry = app(RecordReferenceRegistry::class);
        $resolver = app(RecordReferenceResolver::class);

        foreach ($tabs as $tab) {
            foreach ($tab->getContents() as $content) {
                if ($content instanceof FieldsContent) {
                    foreach ($content->getFields() as $field) {
                        if ($field instanceof RecordReferenceViewField && $field->isVisible($record)) {
                            $appCode = $field->getApplicationCode();
                            $application = $referenceApplications->get($appCode);
                            $provider = $registry->resolve($appCode);
                            $referencedRecord = $field->getRecord($record);

                            if ($application && $provider && $referencedRecord instanceof Model) {
                                $identity = $resolver->identity(
                                    $provider,
                                    $application,
                                    $referencedRecord,
                                    $applicationAccessibleByCode->get($appCode, false)
                                );

                                if ($identity->authorized) {
                                    $referenceFields[$field->getKey()] = [
                                        'variant' => $field->getVariant()->value,
                                        'identity' => $identity,
                                        'facts' => $field->getVariant() === RecordReferenceVariant::Card ? $resolver->cardFacts($provider, $referencedRecord) : [],
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }

        return view('livewire.dynamic-record-view.record-view', [
            'definition' => $definition,
            'record' => $record,
            'section' => $section,
            'tabs' => $tabs,
            'currentTab' => $currentTab,
            'referenceFields' => $referenceFields,
        ]);
    }
}
