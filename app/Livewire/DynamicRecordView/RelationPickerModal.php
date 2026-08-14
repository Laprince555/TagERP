<?php

namespace App\Livewire\DynamicRecordView;

use App\Support\DynamicRecordView\Core\RelationPicker;
use App\Support\DynamicRecordView\Resolution\EmbeddedTableContext;
use App\Support\DynamicRecordView\Resolution\RelationshipMutator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Flux Free modal (see resources/views/livewire/dynamic-record-view/
 * relation-picker-modal.blade.php) that lets a user search for and Link an
 * existing record into one embedded relation. One instance is rendered per
 * embedded TableContent that has relationshipActions()->isLinkable() (see
 * resources/views/components/dynamic-record-view/content.blade.php).
 *
 * Public state stays bounded: at most maximumLoadedResults() rows, each
 * carrying only {id, label} — never a full model list, never a COUNT query.
 * No candidate query runs until openPicker() is actually called.
 */
class RelationPickerModal extends Component
{
    #[Locked]
    public string $recordViewKey;

    #[Locked]
    public int|string $recordId;

    #[Locked]
    public string $section;

    #[Locked]
    public string $tab;

    #[Locked]
    public string $contentKey;

    /** @var class-string */
    #[Locked]
    public string $tableClass;

    public string $search = '';

    /** @var array<int, array{id: int|string, label: string}> */
    public array $results = [];

    public bool $hasMore = false;

    public ?string $error = null;

    public int|string|null $selectedId = null;

    public function mount(
        string $recordViewKey,
        int|string $recordId,
        string $section,
        string $tab,
        string $contentKey,
        string $tableClass,
    ): void {
        $this->recordViewKey = $recordViewKey;
        $this->recordId = $recordId;
        $this->section = $section;
        $this->tab = $tab;
        $this->contentKey = $contentKey;
        $this->tableClass = $tableClass;
    }

    protected function instanceIdentifier(): string
    {
        return implode(':', [$this->recordViewKey, (string) $this->recordId, $this->section, $this->tab, $this->contentKey]);
    }

    protected function modalName(): string
    {
        return 'relation-picker-'.$this->instanceIdentifier();
    }

    /**
     * @return array<string, string>
     */
    protected function getListeners(): array
    {
        return [
            'open-relation-picker.'.$this->instanceIdentifier() => 'openPicker',
        ];
    }

    /**
     * Resets all state and loads the first page. Nothing is queried before
     * this is called — the toolbar Link button dispatches the scoped
     * open-relation-picker event that triggers it.
     */
    public function openPicker(): void
    {
        $this->reset('search', 'results', 'hasMore', 'error', 'selectedId');
        $this->loadResults();
        $this->dispatch('relation-picker-opened.'.$this->modalName());
    }

    public function updatedSearch(): void
    {
        $term = mb_substr(trim($this->search), 0, RelationPicker::MAX_SEARCH_LENGTH);

        if ($term !== $this->search) {
            $this->search = $term;
        }

        $this->results = [];
        $this->hasMore = false;
        $this->loadResults();
    }

    public function loadMore(): void
    {
        $this->loadResults();
    }

    protected function loadResults(): void
    {
        $this->error = null;

        $picker = $this->picker();

        if ($picker === null) {
            $this->error = 'Linking is not available.';

            return;
        }

        if (count($this->results) >= $picker->getMaximumLoadedResults()) {
            $this->hasMore = false;

            return;
        }

        $query = $this->candidateQuery($picker);

        if ($query === null) {
            $this->error = 'Unable to load candidates.';

            return;
        }

        $loadedIds = array_column($this->results, 'id');
        $take = min($picker->getPageSize(), $picker->getMaximumLoadedResults() - count($this->results));

        if ($loadedIds !== []) {
            $query->whereNotIn($query->getModel()->getKeyName(), $loadedIds);
        }

        $page = $query->limit($take + 1)->get();

        $this->hasMore = $page->count() > $take;

        $newResults = $page->take($take)->map(fn (Model $record) => [
            'id' => $record->getKey(),
            'label' => $picker->display($record),
        ])->all();

        $this->results = [...$this->results, ...$newResults];

        if (count($this->results) >= $picker->getMaximumLoadedResults()) {
            $this->hasMore = false;
        }
    }

    /**
     * Candidate query: PK + label/search fields only, never SELECT *, always
     * excludes candidates already related to the current parent, escapes
     * LIKE wildcards in user search input, orders by the search/display
     * field with the primary key as a stable tie-breaker, and never issues a
     * COUNT() — hasMore is derived from fetching one extra row instead.
     */
    protected function candidateQuery(RelationPicker $picker): ?Builder
    {
        [$parent, $content] = app(EmbeddedTableContext::class)->resolveEmbeddedContent(
            $this->tableClass, $this->recordViewKey, $this->recordId, $this->section, $this->tab, $this->contentKey,
        );

        $relationName = $content->getRelation();

        if ($relationName === null) {
            return null;
        }

        $relation = app(EmbeddedTableContext::class)->resolveRelation($parent, $relationName);
        $related = $relation->getRelated();
        $keyName = $related->getKeyName();

        $searchFields = $picker->getSearchable();
        $selectFields = array_values(array_unique([$keyName, ...$searchFields]));

        $query = $related->newQuery()->select($selectFields);

        if ($pickerQuery = $picker->getQuery()) {
            $query = $pickerQuery($query);
        }

        // Never offer candidates already related to this exact parent.
        $query->whereNotIn(
            $keyName,
            $relation->getQuery()->select($related->getQualifiedKeyName()),
        );

        if ($this->search !== '' && $searchFields !== []) {
            $escaped = addcslashes($this->search, '\\%_');

            $query->where(function (Builder $q) use ($searchFields, $escaped): void {
                foreach ($searchFields as $field) {
                    $q->orWhere($field, 'like', "%{$escaped}%");
                }
            });
        }

        $orderField = $searchFields[0] ?? $keyName;

        return $query->orderBy($orderField)->orderBy($keyName);
    }

    protected function picker(): ?RelationPicker
    {
        [, $content] = app(EmbeddedTableContext::class)->resolveEmbeddedContent(
            $this->tableClass, $this->recordViewKey, $this->recordId, $this->section, $this->tab, $this->contentKey,
        );

        return $content->getRelationshipActions()?->getPicker();
    }

    /**
     * Selection alone never mutates anything — an explicit confirmLink()
     * click is required.
     */
    public function selectCandidate(int|string $id): void
    {
        $this->selectedId = $id;
    }

    public function confirmLink(): void
    {
        if ($this->selectedId === null) {
            return;
        }

        try {
            app(RelationshipMutator::class)->link(
                $this->tableClass, $this->recordViewKey, $this->recordId, $this->section, $this->tab, $this->contentKey, $this->selectedId,
            );
        } catch (\Throwable $e) {
            $this->error = 'Unable to link.';

            return;
        }

        $this->dispatch('relationship-linked.'.$this->instanceIdentifier());
        $this->reset('search', 'results', 'hasMore', 'error', 'selectedId');
        $this->dispatch('close-relation-picker.'.$this->modalName());
    }

    public function render()
    {
        return view('livewire.dynamic-record-view.relation-picker-modal', [
            'modalName' => $this->modalName(),
            'picker' => $this->picker(),
        ]);
    }
}
