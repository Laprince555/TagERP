<?php

namespace App\Livewire\DynamicRecordView;

use App\Services\NavigationTreeService;
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
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

    /** Result of the last header action — a flash line above the record. */
    public ?string $actionMessage = null;

    public ?string $actionError = null;

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

    /**
     * Runs one header action. The key is the only thing the client supplies,
     * and it is resolved against authorizedActions() — the permission and the
     * definition's visibility rule are re-checked here on the write path, not
     * merely honoured when the button was rendered.
     *
     * A handler that returns a string is a redirect target: that is how
     * `reverse` lands on the reversal it just created, and how `delete`
     * leaves a page whose record no longer exists.
     */
    public function runAction(string $key): void
    {
        $this->actionMessage = null;
        $this->actionError = null;

        $definition = $this->definition();
        $record = app(RecordResolver::class)->resolveFresh($definition, $this->recordId);
        $action = $definition->findAuthorizedAction($key, $record);

        if ($action === null || $action->isLink() || $action->isForm()) {
            throw new NotFoundHttpException;
        }

        $handler = $action->getHandler();

        if (! method_exists($definition, $handler)) {
            throw new NotFoundHttpException;
        }

        try {
            $result = $definition->{$handler}($record);
        } catch (RuntimeException $exception) {
            $this->actionError = $exception->getMessage();

            return;
        }

        if (is_string($result) && $result !== '') {
            $this->redirect($result, navigate: true);

            return;
        }

        // The handler just changed the record, and the render that follows in
        // this same request would otherwise reuse the copy resolved above —
        // leaving a posted journal still showing Post and Delete.
        app(RecordResolver::class)->resolveFresh($definition, $this->recordId);

        $this->actionMessage = $action->getSuccessMessage() ?? __('Done.');
    }

    /**
     * The edit modal's saved event carries a key only known at runtime, so it
     * is wired here rather than with an #[On] attribute.
     *
     * @return array<string, string>
     */
    protected function getListeners(): array
    {
        $formKey = $this->editFormKey();

        return $formKey === null ? [] : ['dynamic-form-saved.'.$formKey => 'refreshAfterEdit'];
    }

    /**
     * Re-renders the record after its header form saved, so the fields on
     * screen show what was just written rather than what was loaded.
     */
    public function refreshAfterEdit(): void
    {
        $this->actionError = null;
        $this->actionMessage = __('Saved.');
    }

    /**
     * The form key of the single `form()` action, if the definition declares
     * one — used to host its modal and to name the saved-event listener.
     */
    public function editFormKey(): ?string
    {
        foreach ($this->definition()->actions() as $action) {
            if ($action->isForm()) {
                return $action->getFormKey();
            }
        }

        return null;
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

    /**
     * The Application this record belongs to, so the page can carry the same
     * header every index page shows. Null when the definition declares no
     * application code, or when the actor cannot reach it — the record itself
     * is already gated by the definition's own query(), so a missing header
     * withholds context, never access.
     */
    protected function application(DynamicRecordView $definition): ?object
    {
        $code = $definition->applicationCode();

        if ($code === null) {
            return null;
        }

        $application = app(NavigationTreeService::class)->getApplicationByCode($code);

        return app(RecordReferenceAccess::class)->applicationAccessible($application) ? $application : null;
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
            'application' => $this->application($definition),
            'actions' => $actions = $definition->authorizedActions($record),
            // A modal is hosted only for a button that survived the permission
            // and visibility filter — otherwise a posted journal still ships an
            // edit dialog nothing is allowed to open.
            'formActions' => array_values(array_filter($actions, fn ($action) => $action->isForm())),
        ]);
    }
}
