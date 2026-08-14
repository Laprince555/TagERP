<?php

namespace App\Livewire\DynamicTable;

use App\Support\DynamicRecordView\Core\RelationshipActions;
use App\Support\DynamicRecordView\Resolution\EmbeddedTableContext;
use App\Support\DynamicRecordView\Resolution\RelationshipMutator;
use App\Support\DynamicTable\Core\Column;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Exceptions\InvalidModelException;
use App\Support\DynamicTable\Core\Exceptions\MissingTableKeyException;
use App\Support\DynamicTable\Core\Filter;
use App\Support\DynamicTable\Core\Filters\BelongsToFilter;
use App\Support\DynamicTable\Core\SavedTableViewStore;
use App\Support\DynamicTable\Core\SearchDriver;
use App\Support\DynamicTable\Core\Sort;
use App\Support\DynamicTable\Core\TableDefinition;
use App\Support\DynamicTable\Core\TablePreferences;
use App\Support\DynamicTable\Core\TablePreferenceStore;
use App\Support\DynamicTable\Core\TableState;
use App\Support\DynamicTable\Query\TableQueryBuilder;
use App\Support\RecordReference\RecordReferenceAccess;
use App\Support\RecordReference\RecordReferenceRegistry;
use App\Support\RecordReference\RecordReferenceResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Base class for one Dynamic Table instance. Public properties are primitives
 * and arrays only — never Eloquent models, builders, closures, or column/filter
 * objects. The table definition (columns/filters/query/defaultSort) is rebuilt
 * fresh every request via definition() and never serialized into public state.
 *
 * One concrete subclass per table instance; never one component per row/cell.
 */
abstract class Table extends Component
{
    /**
     * Namespaces query-string state so multiple table instances on one page
     * don't collide. Required — mount() throws MissingTableKeyException if unset.
     */
    protected string $tableKey = '';

    /**
     * Temporary instance identity — namespaces query-string params and the
     * Livewire DOM key for one embedded instance so two instances of the
     * same table (e.g. two different parents' Applications tabs) never share
     * search/filter/sort/page state. Deliberately NEVER passed to
     * TablePreferenceStore/SavedTableViewStore — those use storageKey()
     * instead, which stays stable across parents. Public + Locked so it
     * survives Livewire hydration across requests while remaining immune to
     * client tampering. Set automatically from the embed* props when this
     * table is embedded (see mount()); left blank for standalone tables,
     * where instanceIdentifier() just falls back to storageKey().
     */
    #[Locked]
    public string $instanceKey = '';

    /**
     * Bounded scalar embedding context — set once in mount() from a Dynamic
     * Record View's trusted server-side definition, never from raw client
     * input beyond these primitives. When $embedRecordViewKey is set, the
     * query built by definition() is passed through EmbeddedTableContext to
     * layer the declared relation's constraint on top of this table's own
     * query() — the constraint can never be escaped by search/filters/sort/
     * saved views, which only ever add where()/whereHas() on top (see
     * TableQueryBuilder). Leave all blank to use this table standalone,
     * unconstrained — the exact same class works both ways.
     */
    #[Locked]
    public string $embedRecordViewKey = '';

    #[Locked]
    public int|string $embedRecordId = '';

    #[Locked]
    public string $embedSection = '';

    #[Locked]
    public string $embedTab = '';

    #[Locked]
    public string $embedContent = '';

    public string $search = '';

    /**
     * Draft filter values, bound with a plain wire:model (deferred, no .live)
     * so N filter field changes cost one Livewire request when Apply is pressed.
     *
     * @var array<string, array{operator: string, value: mixed}>
     */
    public array $filters = [];

    /** @var array<string, array{operator: string, value: mixed}> */
    public array $appliedFilters = [];

    /** @var array<int, array{column: string, direction: string}> */
    public array $sorts = [];

    public int $page = 1;

    public ?string $cursor = null;

    /** @var array<int, string> */
    public array $selectedIds = [];

    public bool $selectAllMatching = false;

    public int $perPage = TableState::DEFAULT_PER_PAGE;

    /** @var string[] */
    public array $visibleColumns = [];

    /** @var string[] */
    public array $columnOrder = [];

    /** @var array<int, array{id: int, name: string, is_default: bool}> loaded once in mount(), never re-queried per render */
    public array $savedViews = [];

    public string $newViewName = '';

    public ?string $saveViewError = null;

    /** Tracks the last applied/saved view for display only — not re-validated against live drift from that view's original state. */
    public ?int $activeViewId = null;

    /** @var array<string, string> draft search text per BelongsToFilter key, keyed by filter key */
    public array $belongsToSearch = [];

    /** @var array<string, array<int, array{id: int|string, label: string}>> current option list per BelongsToFilter key — empty until searched */
    public array $belongsToOptions = [];

    public const BELONGS_TO_MIN_SEARCH_LENGTH = 2;

    public const BELONGS_TO_MAX_RESULTS = 20;

    /**
     * In-request-only cache (never serialized, never a public property) so
     * resolveBelongsToLabels() for the same filter+value is queried once per
     * render even though both the picker's "selected" display and the active
     * filter chip summary need the same labels. Keyed by "filterKey|json(value)"
     * so a genuinely different value (draft vs. applied differ) still resolves
     * separately and correctly.
     *
     * @var array<string, array<int, array{id: int|string, label: string}>>
     */
    protected array $belongsToLabelsCache = [];

    /**
     * @return array<int, Column>
     */
    abstract protected function columns(): array;

    /**
     * @return array<int, Filter>
     */
    abstract protected function filters(): array;

    /**
     * Set this to get a safe default base query without overriding query()
     * at all, e.g. `protected ?string $model = Customer::class;`.
     *
     * @var class-string<Model>|null
     */
    protected ?string $model = null;

    /**
     * Default base query, built from `$model`. Override to scope it (tenant,
     * permissions, soft-delete visibility, etc.) — the original spec's
     * `parent::query()->visibleTo(auth()->user())` pattern:
     *
     *     protected function query(): Builder
     *     {
     *         return parent::query()->visibleTo(auth()->user());
     *     }
     *
     * A table with neither `$model` set nor query() overridden throws
     * InvalidModelException at render time, not a silent empty result set.
     */
    protected function query(): Builder
    {
        if ($this->model === null) {
            throw InvalidModelException::forMissingModelOrQuery(static::class);
        }

        if (! is_subclass_of($this->model, Model::class)) {
            throw InvalidModelException::forInvalidModel($this->model);
        }

        return ($this->model)::query();
    }

    /**
     * @return Sort[]
     */
    protected function defaultSort(): array
    {
        return [];
    }

    public function mount(
        string $embedRecordViewKey = '',
        int|string $embedRecordId = '',
        string $embedSection = '',
        string $embedTab = '',
        string $embedContent = '',
    ): void {
        $this->embedRecordViewKey = $embedRecordViewKey;
        $this->embedRecordId = $embedRecordId;
        $this->embedSection = $embedSection;
        $this->embedTab = $embedTab;
        $this->embedContent = $embedContent;

        if ($embedRecordViewKey !== '') {
            $this->instanceKey = implode(':', [
                $embedRecordViewKey, (string) $embedRecordId, $embedSection, $embedTab, $embedContent,
            ]);
        }

        if ($this->storageKey() === '') {
            throw MissingTableKeyException::make();
        }

        $definition = $this->definition();

        // Preferences are loaded once here, per request — never re-queried in render().
        // Guests get definition defaults; there is no user to own a preference row.
        $stored = auth()->check() ? $this->preferenceStore()->get(auth()->user(), $this->storageKey()) : null;
        $prefs = TablePreferences::normalize($definition, $stored);

        $this->visibleColumns = $prefs->visibleColumns();
        $this->columnOrder = $prefs->columnOrder;
        $this->perPage = $prefs->perPage;
        $this->sorts = array_map(fn (Sort $sort) => $sort->toArray(), $this->defaultSort());

        if (auth()->check()) {
            $this->savedViews = array_map(
                fn (array $v) => ['id' => $v['id'], 'name' => $v['name'], 'is_default' => $v['is_default']],
                $this->savedViewStore()->all(auth()->user(), $this->storageKey()),
            );

            $default = collect($this->savedViews)->firstWhere('is_default', true);
            if ($default) {
                $this->applyView($default['id']);
            }
        }
    }

    /**
     * Permanent storage identity — used for TablePreferenceStore/
     * SavedTableViewStore. Stable for the logical table definition; NEVER
     * includes a parent record id, so standalone and every embedded instance
     * of the same table class share the same preferences/saved views.
     */
    protected function storageKey(): string
    {
        return $this->tableKey;
    }

    /**
     * Temporary instance identity — used for query-string namespacing (and
     * available for DOM keys). Falls back to storageKey() when this table
     * isn't embedded, so standalone tables keep their original single-key
     * behavior unchanged.
     */
    protected function instanceIdentifier(): string
    {
        return $this->instanceKey !== '' ? $this->instanceKey : $this->storageKey();
    }

    protected function preferenceStore(): TablePreferenceStore
    {
        return app(TablePreferenceStore::class);
    }

    protected function savedViewStore(): SavedTableViewStore
    {
        return app(SavedTableViewStore::class);
    }

    /**
     * Capture current search/filters/sort/visibility/order/per-page as a named view.
     * Re-saving an existing name updates it in place (one write).
     */
    public function saveCurrentView(string $name): void
    {
        $this->saveViewError = null;

        if (! auth()->check()) {
            return;
        }

        try {
            $viewId = $this->savedViewStore()->create(auth()->user(), $this->storageKey(), $name, $this->rawState());
        } catch (ValidationException $e) {
            $this->saveViewError = collect($e->errors())->flatten()->first();

            return;
        }

        $this->newViewName = '';
        $this->activeViewId = $viewId;
        $this->savedViews = array_map(
            fn (array $v) => ['id' => $v['id'], 'name' => $v['name'], 'is_default' => $v['is_default']],
            $this->savedViewStore()->all(auth()->user(), $this->storageKey()),
        );
    }

    public function renameView(int $viewId, string $newName): void
    {
        $this->saveViewError = null;

        if (! auth()->check()) {
            return;
        }

        try {
            $this->savedViewStore()->rename(auth()->user(), $this->storageKey(), $viewId, $newName);
        } catch (ValidationException $e) {
            $this->saveViewError = collect($e->errors())->flatten()->first();

            return;
        }

        $this->savedViews = array_map(
            fn (array $v) => ['id' => $v['id'], 'name' => $v['name'], 'is_default' => $v['is_default']],
            $this->savedViewStore()->all(auth()->user(), $this->storageKey()),
        );
    }

    public function updateView(int $viewId): void
    {
        $this->saveViewError = null;

        if (! auth()->check()) {
            return;
        }

        try {
            $this->savedViewStore()->update(auth()->user(), $this->storageKey(), $viewId, $this->rawState());
        } catch (ValidationException $e) {
            $this->saveViewError = collect($e->errors())->flatten()->first();

            return;
        }

        $this->savedViews = array_map(
            fn (array $v) => ['id' => $v['id'], 'name' => $v['name'], 'is_default' => $v['is_default']],
            $this->savedViewStore()->all(auth()->user(), $this->storageKey()),
        );
    }

    /**
     * Overlay a saved view's configuration onto current state. Re-normalized
     * against the live definition so removed/unauthorized columns and filters
     * are silently dropped rather than crashing the table.
     */
    public function applyView(int $viewId): void
    {
        if (! auth()->check()) {
            return;
        }

        $view = $this->savedViewStore()->find(auth()->user(), $this->storageKey(), $viewId);

        if (! $view) {
            return;
        }

        $config = $view['configuration'];
        $definition = $this->definition();
        $state = TableState::normalize($config, $definition);

        $this->search = $state->search;
        $this->appliedFilters = $state->filters;
        $this->filters = $state->filters;
        $this->sorts = $state->sorts;
        $this->perPage = $state->perPage;
        $this->visibleColumns = $state->visibleColumns;
        $this->columnOrder = $state->columnOrder;
        $this->activeViewId = $viewId;
        $this->resetTablePage();
    }

    public function deleteView(int $viewId): void
    {
        if (! auth()->check()) {
            return;
        }

        $this->savedViewStore()->delete(auth()->user(), $this->storageKey(), $viewId);

        if ($this->activeViewId === $viewId) {
            $this->activeViewId = null;
        }

        $this->savedViews = array_map(
            fn (array $v) => ['id' => $v['id'], 'name' => $v['name'], 'is_default' => $v['is_default']],
            $this->savedViewStore()->all(auth()->user(), $this->storageKey()),
        );
    }

    /**
     * Resets search/filters/sort/columns/per-page back to the table definition's
     * own defaults — distinct from resetPreferences(), which only resets column
     * visibility/order/per-page. Clears which saved view (if any) is active.
     */
    public function resetToTableDefaults(): void
    {
        $this->search = '';
        $this->filters = [];
        $this->appliedFilters = [];
        $this->sorts = array_map(fn (Sort $sort) => $sort->toArray(), $this->defaultSort());
        $this->activeViewId = null;
        $this->resetTablePage();
        $this->resetPreferences();
    }

    public function setDefaultView(int $viewId): void
    {
        if (! auth()->check()) {
            return;
        }

        $this->savedViewStore()->setDefault(auth()->user(), $this->storageKey(), $viewId);

        $this->savedViews = array_map(
            fn (array $v) => ['id' => $v['id'], 'name' => $v['name'], 'is_default' => $v['is_default']],
            $this->savedViewStore()->all(auth()->user(), $this->storageKey()),
        );
    }

    protected function persistPreferences(): void
    {
        if (! auth()->check()) {
            return;
        }

        $prefs = new TablePreferences(
            hiddenColumns: array_values(array_diff($this->columnOrder, $this->visibleColumns)),
            columnOrder: $this->columnOrder,
            perPage: $this->perPage,
            density: 'comfortable',
        );

        $this->preferenceStore()->save(auth()->user(), $this->storageKey(), $prefs->toArray());
    }

    public function resetPreferences(): void
    {
        if (! auth()->check()) {
            return;
        }

        $this->preferenceStore()->delete(auth()->user(), $this->storageKey());

        $definition = $this->definition();
        $prefs = TablePreferences::normalize($definition, null);

        $this->visibleColumns = $prefs->visibleColumns();
        $this->columnOrder = $prefs->columnOrder;
        $this->perPage = $prefs->perPage;
        $this->resetTablePage();
    }

    /**
     * Namespaces every query-string param by the temporary instance identity
     * (not storageKey()) so two embedded instances of the same table for
     * different parent records never collide, while still matching
     * storageKey() for a plain standalone table.
     *
     * @return array<string, array{as: string}>
     */
    protected function queryString(): array
    {
        $prefix = $this->instanceIdentifier();

        return [
            'search' => ['as' => "{$prefix}_search"],
            'appliedFilters' => ['as' => "{$prefix}_filters"],
            'sorts' => ['as' => "{$prefix}_sorts"],
            'page' => ['as' => "{$prefix}_page"],
            'cursor' => ['as' => "{$prefix}_cursor"],
            'perPage' => ['as' => "{$prefix}_perPage"],
            'visibleColumns' => ['as' => "{$prefix}_visible"],
        ];
    }

    protected function definition(): TableDefinition
    {
        return new TableDefinition(
            tableKey: $this->storageKey(),
            columns: $this->columns(),
            filters: $this->filters(),
            query: fn () => $this->resolvedQuery(),
            defaultSort: $this->defaultSort(),
        );
    }

    /**
     * This table's own authorized base query (query()), with the embedding
     * relation constraint layered on top via EmbeddedTableContext when this
     * instance is embedded — never in place of it. Standalone tables
     * (embedRecordViewKey === '') skip the adapter entirely and get exactly
     * query() back, unconstrained — the same class works both ways.
     */
    protected function resolvedQuery(): Builder
    {
        $query = $this->query();

        if ($this->embedRecordViewKey === '') {
            return $query;
        }

        return app(EmbeddedTableContext::class)->constrain(
            $query,
            static::class,
            $this->embedRecordViewKey,
            $this->embedRecordId,
            $this->embedSection,
            $this->embedTab,
            $this->embedContent,
        );
    }

    /**
     * The relationshipActions() configured on this instance's own embedded
     * content block, if any — resolved fresh (not cached on the component)
     * so a definition change or authorization change is picked up on the
     * very next render. Null for a standalone (non-embedded) table.
     */
    protected function embeddedRelationshipActions(): ?RelationshipActions
    {
        if ($this->embedRecordViewKey === '') {
            return null;
        }

        [, $content] = app(EmbeddedTableContext::class)->resolveEmbeddedContent(
            static::class,
            $this->embedRecordViewKey,
            $this->embedRecordId,
            $this->embedSection,
            $this->embedTab,
            $this->embedContent,
        );

        return $content->getRelationshipActions();
    }

    /**
     * Row-level Unlink action — only reachable when this instance is
     * embedded and its content block declares an authorized unlinkable
     * relationshipActions(). The mutation itself re-authorizes and
     * re-validates everything from scratch (see RelationshipMutator); this
     * method only forwards the bounded scalar ids already carried as
     * #[Locked] public state.
     */
    public function unlinkRelated(int|string $relatedId): void
    {
        $actions = $this->embeddedRelationshipActions();

        if ($actions === null || ! $actions->isUnlinkable()) {
            return;
        }

        app(RelationshipMutator::class)->unlink(
            static::class,
            $this->embedRecordViewKey,
            $this->embedRecordId,
            $this->embedSection,
            $this->embedTab,
            $this->embedContent,
            $relatedId,
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function rawState(): array
    {
        return [
            'search' => $this->search,
            'filters' => $this->appliedFilters,
            'sorts' => $this->sorts,
            'page' => $this->page,
            'perPage' => $this->perPage,
            'visibleColumns' => $this->visibleColumns,
            'columnOrder' => $this->columnOrder,
            'cursor' => $this->cursor,
        ];
    }

    /**
     * Search is submit-triggered (Enter/button), not live — see the search
     * partial's wire:model without .live. If this is ever changed to live
     * search, it must carry wire:model.live.debounce.400ms or slower.
     */
    public function submitSearch(): void
    {
        $this->resetTablePage();
    }

    public function applyFilters(): void
    {
        $this->appliedFilters = $this->filters;
        $this->resetTablePage();
    }

    public function clearFilters(): void
    {
        $this->filters = [];
        $this->appliedFilters = [];
        $this->resetTablePage();
    }

    /**
     * Clears a single active filter chip (both the draft and applied value)
     * without discarding any other applied filter.
     */
    public function clearFilter(string $key): void
    {
        unset($this->filters[$key], $this->appliedFilters[$key]);
        $this->resetTablePage();
    }

    /**
     * Async option search for a BelongsToFilter. Requires the filter to exist
     * and be authorized (routes through definition()->filter()), a minimum
     * search length, selects only the related model's key + declared
     * display/search fields (never the whole row), respects the related
     * model's own scopes (soft deletes, tenant global scopes, etc. — via a
     * fresh query on the relation's related model), and caps result count.
     * No query runs until this is actually called (i.e. the picker was
     * opened/typed into) — never eagerly preloaded.
     */
    public function searchBelongsTo(string $filterKey, string $term): void
    {
        $filter = $this->definition()->filter($filterKey);

        if (! $filter instanceof BelongsToFilter) {
            return;
        }

        $term = mb_substr(trim($term), 0, TableState::MAX_SEARCH_LENGTH);
        $this->belongsToSearch[$filterKey] = $term;

        if (mb_strlen($term) < self::BELONGS_TO_MIN_SEARCH_LENGTH) {
            $this->belongsToOptions[$filterKey] = [];

            return;
        }

        $this->belongsToOptions[$filterKey] = $this->fetchBelongsToOptions($filter, function (Builder $query) use ($filter, $term): Builder {
            $escaped = addcslashes($term, '\\%_');
            $fields = $filter->getSearchFields() ?: [$query->getModel()->getKeyName()];

            return $query->where(function (Builder $q) use ($fields, $escaped): void {
                foreach ($fields as $field) {
                    $q->orWhere($field, 'like', "%{$escaped}%");
                }
            });
        });
    }

    /**
     * Resolves display labels for the filter's currently selected id(s) — so
     * the picker can show "France" instead of a bare "12" for a value that
     * arrived via a saved view or restored query-string state, without
     * requiring the user to re-search for it. Same select-narrowing and
     * result cap as searchBelongsTo().
     *
     * @return array<int, array{id: int|string, label: string}>
     */
    protected function resolveBelongsToLabels(string $filterKey, mixed $value = null): array
    {
        $filter = $this->definition()->filter($filterKey);

        if (! $filter instanceof BelongsToFilter) {
            return [];
        }

        $value ??= $this->filters[$filterKey]['value'] ?? null;

        if ($value === null || $value === '' || $value === []) {
            return [];
        }

        $cacheKey = $filterKey.'|'.json_encode($value);

        if (isset($this->belongsToLabelsCache[$cacheKey])) {
            return $this->belongsToLabelsCache[$cacheKey];
        }

        $ids = array_values(array_filter(
            is_array($value) ? $value : [$value],
            fn ($v) => is_numeric($v) || (is_string($v) && $v !== ''),
        ));

        if ($ids === []) {
            return $this->belongsToLabelsCache[$cacheKey] = [];
        }

        return $this->belongsToLabelsCache[$cacheKey] = $this->fetchBelongsToOptions(
            $filter,
            fn (Builder $query) => $query->whereIn($query->getModel()->getKeyName(), $ids),
        );
    }

    /**
     * @param  callable(Builder): Builder  $scope
     * @return array<int, array{id: int|string, label: string}>
     */
    protected function fetchBelongsToOptions(BelongsToFilter $filter, callable $scope): array
    {
        $model = ($this->definition()->query)()->getModel();

        // Relation::noConstraints() builds the relation's query WITHOUT the
        // per-instance "belongs to this specific model" constraint a plain
        // $model->{$relationName}()->getQuery() would carry for a BelongsTo —
        // while still preserving any ->where(...)/scope chained onto the
        // relation method's own definition (e.g. `belongsTo(Country::class)
        // ->where('active', true)`) and the related model's global scopes,
        // neither of which a bare $related->newQuery() would include.
        $baseQuery = Relation::noConstraints(
            fn () => $model->{$filter->getKey()}()->getQuery()
        );
        $keyName = $baseQuery->getModel()->getKeyName();
        $searchFields = $filter->getSearchFields() ?: [$keyName];

        $query = $baseQuery->select(array_values(array_unique([$keyName, ...$searchFields])));

        // Server-defined additional constraint (e.g. tenant scoping), applied on
        // top of — never instead of — the relation's own query.
        if ($optionsQuery = $filter->getOptionsQueryCallback()) {
            $query = $optionsQuery($query);
        }

        $query = $scope($query);

        $displayUsing = $filter->getDisplayCallback() ?? fn ($option) => (string) $option->getKey();
        $authorizeOption = $filter->getAuthorizeOptionCallback();

        $options = $query->limit(self::BELONGS_TO_MAX_RESULTS)->get();

        if ($authorizeOption) {
            $options = $options->filter($authorizeOption);
        }

        return $options
            ->map(fn ($option) => ['id' => $option->getKey(), 'label' => (string) $displayUsing($option)])
            ->values()
            ->all();
    }

    /**
     * Records a picked option — but only after re-validating the id through
     * the SAME authorized options query used for search/label resolution
     * (relation constraints, ->optionsQuery(), ->authorizeOption() all
     * re-checked). A forged id that doesn't come back from that query is
     * silently ignored, never accepted on faith. Supports non-integer
     * (UUID/ULID/string) primary keys — never force-cast to int.
     */
    public function selectBelongsToOption(string $filterKey, int|string $id): void
    {
        $filter = $this->definition()->filter($filterKey);

        if (! $filter instanceof BelongsToFilter) {
            return;
        }

        $verified = $this->fetchBelongsToOptions($filter, fn (Builder $query) => $query->where($query->getModel()->getKeyName(), $id));

        if ($verified === []) {
            return;
        }

        $verifiedId = $verified[0]['id'];

        if ($filter->isMultiple()) {
            $current = (array) ($this->filters[$filterKey]['value'] ?? []);
            if (! in_array($verifiedId, $current, false)) {
                $current[] = $verifiedId;
            }
            $this->filters[$filterKey] = ['operator' => 'in', 'value' => $current];
        } else {
            $this->filters[$filterKey] = ['operator' => 'equals', 'value' => $verifiedId];
        }

        $this->belongsToSearch[$filterKey] = '';
        $this->belongsToOptions[$filterKey] = [];
    }

    public function removeBelongsToOption(string $filterKey, int|string $id): void
    {
        $filter = $this->definition()->filter($filterKey);

        if (! $filter instanceof BelongsToFilter || ! $filter->isMultiple()) {
            return;
        }

        $remaining = array_values(array_filter(
            (array) ($this->filters[$filterKey]['value'] ?? []),
            fn ($v) => (string) $v !== (string) $id,
        ));

        $this->filters[$filterKey] = $remaining === [] ? [] : ['operator' => 'in', 'value' => $remaining];
    }

    /**
     * Single-column sort: replaces the entire sort list. Clicking the same
     * column again flips its direction.
     */
    public function sortBy(string $column): void
    {
        $definition = $this->definition();

        if (! $definition->column($column)?->isSortable()) {
            return;
        }

        $current = collect($this->sorts)->firstWhere('column', $column);
        $direction = ($current && $current['direction'] === 'asc') ? 'desc' : 'asc';

        $this->sorts = [['column' => $column, 'direction' => $direction]];
        $this->resetTablePage();
    }

    /**
     * Additive multi-sort: shift-click a header to add it as the next sort
     * priority (or flip its direction if already sorted) without discarding
     * the existing sort list. Capped at TableState::MAX_SORTS defensively —
     * TableState::normalize() enforces the real limit at query time regardless.
     */
    public function sortByAdditive(string $column): void
    {
        $definition = $this->definition();

        if (! $definition->column($column)?->isSortable()) {
            return;
        }

        $index = collect($this->sorts)->search(fn ($sort) => $sort['column'] === $column);

        if ($index !== false) {
            $this->sorts[$index]['direction'] = $this->sorts[$index]['direction'] === 'asc' ? 'desc' : 'asc';
        } elseif (count($this->sorts) < TableState::MAX_SORTS) {
            $this->sorts[] = ['column' => $column, 'direction' => 'asc'];
        }

        $this->resetTablePage();
    }

    /**
     * Removes one column from the active multi-sort without affecting the others.
     */
    public function removeSort(string $column): void
    {
        $this->sorts = array_values(array_filter($this->sorts, fn ($sort) => $sort['column'] !== $column));
        $this->resetTablePage();
    }

    /**
     * Resets sorting to the table definition's own defaultSort() — narrower
     * than resetToTableDefaults(), which also clears search/filters/columns.
     */
    public function resetSort(): void
    {
        $this->sorts = array_map(fn (Sort $sort) => $sort->toArray(), $this->defaultSort());
        $this->resetTablePage();
    }

    public function setPerPage(int $perPage): void
    {
        $this->perPage = in_array($perPage, TableState::PER_PAGE_OPTIONS, true) ? $perPage : TableState::DEFAULT_PER_PAGE;
        $this->resetTablePage();
        $this->persistPreferences();
    }

    public function toggleColumn(string $key): void
    {
        $column = $this->definition()->column($key);

        if (! $column || ! $column->isToggleable()) {
            return;
        }

        $this->visibleColumns = in_array($key, $this->visibleColumns, true)
            ? array_values(array_diff($this->visibleColumns, [$key]))
            : [...$this->visibleColumns, $key];

        $this->persistPreferences();
    }

    /**
     * Persist a completed column reorder (one write per drop, not per pointer move).
     *
     * @param  string[]  $order  Full ordered list of toggleable column keys, as produced by wire:sort.
     */
    public function reorderColumns(array $order): void
    {
        // ->values() is required: collect($this->definition()->columns) is keyed by column key
        // (e.g. 'id' => Column), so ->map()->all() without it returns an associative array like
        // ['id' => 'id'] instead of [0 => 'id'] — silently corrupting the later array-spread merge
        // into a mixed-key array that no longer behaves like a plain ordered list.
        $authorizedToggleable = collect($this->definition()->columns)
            ->filter(fn (Column $col) => $col->isToggleable() && $col->isVisible())
            ->map(fn (Column $col) => $col->getKey())
            ->values()
            ->all();

        $fixed = collect($this->definition()->columns)
            ->filter(fn (Column $col) => ! $col->isToggleable() && $col->isVisible())
            ->map(fn (Column $col) => $col->getKey())
            ->values()
            ->all();

        $safeOrder = array_values(array_intersect($order, $authorizedToggleable));
        // Include any authorized columns missing from the incoming order (never drop a column silently).
        foreach ($authorizedToggleable as $key) {
            if (! in_array($key, $safeOrder, true)) {
                $safeOrder[] = $key;
            }
        }

        $this->columnOrder = [...$fixed, ...$safeOrder];
        $this->persistPreferences();
    }

    /**
     * wire:sort handler for drag-and-drop column reordering — see
     * resources/views/components/dynamic-table/column-manager.blade.php.
     * Moves $item to $position among the toggleable columns, then reuses
     * reorderColumns()'s authorization-safe merge (fixed columns pinned,
     * unauthorized/unknown keys can never be injected). One write per
     * completed drop, matching Livewire's wire:sort semantics (fires once on
     * drop, not per pointer move).
     */
    public function sortColumns(string $item, int $position): void
    {
        $authorizedToggleable = collect($this->definition()->columns)
            ->filter(fn (Column $col) => $col->isToggleable() && $col->isVisible())
            ->map(fn (Column $col) => $col->getKey())
            ->all();

        if (! in_array($item, $authorizedToggleable, true)) {
            return;
        }

        $currentToggleableOrder = array_values(array_intersect($this->columnOrder, $authorizedToggleable));
        $withoutItem = array_values(array_filter($currentToggleableOrder, fn ($key) => $key !== $item));
        array_splice($withoutItem, max(0, $position), 0, [$item]);

        $this->reorderColumns($withoutItem);
    }

    public function gotoPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    protected function resetTablePage(): void
    {
        $this->page = 1;
    }

    public function selectRow(int|string $id): void
    {
        $idStr = (string) $id;
        if (in_array($idStr, $this->selectedIds, true)) {
            $this->selectedIds = array_values(array_diff($this->selectedIds, [$idStr]));
            $this->selectAllMatching = false;
        } else {
            $this->selectedIds[] = $idStr;
        }
    }

    public function selectPage(array $pageIds): void
    {
        $pageIdsStr = array_map(fn ($id) => (string) $id, $pageIds);
        $allSelected = count(array_intersect($pageIdsStr, $this->selectedIds)) === count($pageIdsStr);

        if ($allSelected) {
            $this->selectedIds = array_values(array_diff($this->selectedIds, $pageIdsStr));
            $this->selectAllMatching = false;
        } else {
            $this->selectedIds = array_values(array_unique(array_merge($this->selectedIds, $pageIdsStr)));
        }
    }

    public function toggleSelectAllMatching(): void
    {
        $this->selectAllMatching = ! $this->selectAllMatching;
    }

    public function clearSelection(): void
    {
        $this->selectedIds = [];
        $this->selectAllMatching = false;
    }

    public function export(): StreamedResponse
    {
        $definition = $this->definition();
        $state = TableState::normalize($this->rawState(), $definition);
        $builder = new TableQueryBuilder($definition, $this->searchDriver());
        $query = $builder->query($state);

        if (! $this->selectAllMatching && count($this->selectedIds) > 0) {
            $query->whereIn($query->getModel()->getQualifiedKeyName(), $this->selectedIds);
        }

        $filename = $this->storageKey().'-export-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query, $state, $definition) {
            $handle = fopen('php://output', 'w');

            $columns = $state->orderedVisibleColumns();
            $headers = array_map(fn ($key) => $definition->column($key)?->getLabel() ?? $key, $columns);
            fputcsv($handle, $headers);

            $query->chunk(500, function ($rows) use ($handle, $columns, $definition) {
                foreach ($rows as $row) {
                    $line = [];
                    foreach ($columns as $key) {
                        $col = $definition->column($key);
                        if ($col) {
                            $rawVal = data_get($row, $col->getField());
                            $line[] = $col->formatValue($rawVal, $row);
                        } else {
                            $line[] = '';
                        }
                    }
                    fputcsv($handle, $line);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function bulkDelete(): void
    {
        $definition = $this->definition();
        $state = TableState::normalize($this->rawState(), $definition);
        $builder = new TableQueryBuilder($definition, $this->searchDriver());
        $query = $builder->query($state);

        if (! $this->selectAllMatching) {
            if (count($this->selectedIds) === 0) {
                return;
            }
            $query->whereIn($query->getModel()->getQualifiedKeyName(), $this->selectedIds);
        }

        $query->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                $row->delete();
            }
        });

        $this->clearSelection();
        $this->resetTablePage();
    }

    /**
     * Override to swap in a different search implementation, e.g.
     * `return new ScoutSearchDriver();` for a Scout-backed model. Defaults to
     * plain SQL LIKE search (DatabaseSearchDriver) when not overridden.
     */
    protected function searchDriver(): ?SearchDriver
    {
        return null;
    }

    /**
     * 'standard' (default): length-aware pagination, one count() query, shows
     * total/last page. 'simple': no count() query — cheaper for large tables,
     * but the paginator has no total()/lastPage(), so the pagination view
     * must never call those on it. 'cursor' is not implemented by
     * TableQueryBuilder (see docs/dynamic-table/sorting-pagination.md) —
     * requesting it throws immediately rather than silently falling back.
     */
    protected function paginationMode(): string
    {
        return 'standard';
    }

    /**
     * Listens for a link event scoped to this exact embedded instance (see
     * RelationPickerModal::confirmLink()), namespaced by instanceIdentifier()
     * so two embedded tables on the same page never cross-trigger each
     * other's re-render. Empty body: a handled listener re-renders the
     * component on its own.
     *
     * @return array<string, string>
     */
    protected function getListeners(): array
    {
        return [
            'relationship-linked.'.$this->instanceIdentifier() => 'refreshAfterLink',
        ];
    }

    public function refreshAfterLink(): void {}

    public function render(): View
    {
        $definition = $this->definition();
        $state = TableState::normalize($this->rawState(), $definition);

        $mode = $this->paginationMode();
        $builder = new TableQueryBuilder($definition, $this->searchDriver());

        $rows = match ($mode) {
            'standard' => $builder->paginate($state),
            'simple' => $builder->simplePaginate($state),
            'cursor' => $builder->cursorPaginate($state),
            default => throw new \InvalidArgumentException("Unsupported pagination mode [{$mode}] — use 'standard', 'simple', or 'cursor'."),
        };

        // Resolved once per render, only for BelongsToFilters that currently have a
        // selected value — never a query in the Blade view itself.
        $belongsToSelectedLabels = [];
        foreach ($definition->authorizedFilters() as $key => $filter) {
            if ($filter instanceof BelongsToFilter && ! empty($this->filters[$key]['value'] ?? null)) {
                $belongsToSelectedLabels[$key] = $this->resolveBelongsToLabels($key);
            }
        }

        // One bounded query for every Application referenced by a *visible*
        // Record Reference column — never per row, never for hidden columns.
        $referenceCodes = collect($this->visibleColumns)
            ->map(fn (string $key) => $definition->column($key))
            ->filter(fn (?Column $column) => $column instanceof RecordReferenceColumn)
            ->map(fn (RecordReferenceColumn $column) => $column->getApplicationCode())
            ->unique()
            ->values()
            ->all();

        $referenceApplications = app(RecordReferenceAccess::class)->applications($referenceCodes);
        $referenceCells = $this->recordReferenceCells($definition, $state->orderedVisibleColumns(), $rows, $referenceApplications);

        $relationshipActions = $this->embeddedRelationshipActions();

        return view('livewire.dynamic-table.table', [
            'definition' => $definition,
            'state' => $state,
            'rows' => $rows,
            'belongsToSelectedLabels' => $belongsToSelectedLabels,
            'referenceApplications' => $referenceApplications,
            'referenceCells' => $referenceCells,
            'activeFilterChips' => $this->activeFilterChips($definition),
            'hasDraftFilterChanges' => $this->filters !== $this->appliedFilters,
            'orderedVisibleColumns' => $state->orderedVisibleColumns(),
            'relationshipActions' => $relationshipActions,
            'instanceIdentifier' => $this->instanceIdentifier(),
        ]);
    }

    /**
     * Builds record-reference DTOs outside Blade. Provider and Application
     * lookups are per render; row-level work is pure and query-free.
     *
     * @param  string[]  $visibleColumns
     * @param  iterable<int, Model>  $rows
     * @return array<string, array<string, array{variant: string, identity: mixed, facts: array}>>
     */
    protected function recordReferenceCells(TableDefinition $definition, array $visibleColumns, iterable $rows, $referenceApplications): array
    {
        $registry = app(RecordReferenceRegistry::class);
        $resolver = app(RecordReferenceResolver::class);
        $access = app(RecordReferenceAccess::class);

        $applicationAccessibleByCode = $referenceApplications->mapWithKeys(
            fn ($application, $code) => [$code => $access->applicationAccessible($application)],
        );

        $columns = collect($visibleColumns)
            ->mapWithKeys(fn (string $key) => [$key => $definition->column($key)])
            ->filter(fn (?Column $column) => $column instanceof RecordReferenceColumn);

        if ($columns->isEmpty()) {
            return [];
        }

        $cells = [];

        foreach ($rows as $row) {
            foreach ($columns as $key => $column) {
                /** @var RecordReferenceColumn $column */
                $application = $referenceApplications->get($column->getApplicationCode());
                $provider = $registry->resolve($column->getApplicationCode());
                $referencedRecord = $column->getRecord($row);

                if (! $application || ! $provider || ! $referencedRecord instanceof Model) {
                    continue;
                }

                $identity = $resolver->identity(
                    $provider,
                    $application,
                    $referencedRecord,
                    $applicationAccessibleByCode->get($column->getApplicationCode(), false),
                );

                if (! $identity->authorized) {
                    continue;
                }

                $cells[(string) $row->getKey()][$key] = [
                    'variant' => $column->getVariant()->value,
                    'identity' => $identity,
                    'facts' => $column->getVariant()->value === 'card' ? $resolver->cardFacts($provider, $referencedRecord) : [],
                ];
            }
        }

        return $cells;
    }

    /**
     * Builds a human-readable chip per currently APPLIED filter (never the
     * unapplied draft) — resolved once here, never in the Blade view.
     *
     * @return array<int, array{key: string, label: string, summary: string}>
     */
    protected function activeFilterChips(TableDefinition $definition): array
    {
        $chips = [];

        foreach ($this->appliedFilters as $key => $entry) {
            $filter = $definition->filter($key);

            if (! $filter) {
                continue;
            }

            $chips[] = [
                'key' => $key,
                'label' => $filter->getLabel(),
                'summary' => $this->summarizeFilterValue($filter, $entry),
            ];
        }

        return $chips;
    }

    /**
     * @param  array{operator?: string, value?: mixed}  $entry
     */
    protected function summarizeFilterValue(Filter $filter, array $entry): string
    {
        $operator = $entry['operator'] ?? '';
        $value = $entry['value'] ?? null;

        if (in_array($operator, ['is_empty', 'is_not_empty', 'today', 'yesterday', 'this_week', 'this_month'], true)) {
            return str($operator)->headline()->toString();
        }

        if ($filter instanceof BelongsToFilter) {
            $labels = collect($this->resolveBelongsToLabels($filter->getKey(), $value))->pluck('label');

            return $labels->isEmpty() ? '…' : $labels->implode(', ');
        }

        if (is_array($value)) {
            return implode(' – ', array_map(fn ($v) => (string) $v, $value));
        }

        return is_bool($value) ? ($value ? __('Yes') : __('No')) : (string) $value;
    }
}
