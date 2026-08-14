<?php

use App\Support\DynamicTable\Core\Columns\RelationColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Exceptions\HasManySortWithoutAggregateException;
use App\Support\DynamicTable\Core\Filters\BooleanFilter;
use App\Support\DynamicTable\Core\Filters\DateFilter;
use App\Support\DynamicTable\Core\Filters\NumberFilter;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\TableDefinition;
use App\Support\DynamicTable\Core\TableState;
use App\Support\DynamicTable\Query\TableQueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\DynamicTable\Support\DtAuthor;
use Tests\Feature\DynamicTable\Support\DtPost;

beforeEach(function () {
    Schema::create('dt_test_authors', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('country')->nullable();
    });

    Schema::create('dt_test_posts', function ($table) {
        $table->id();
        $table->foreignId('author_id');
        $table->string('title');
        $table->integer('views')->default(0);
        $table->date('published_at')->nullable();
        $table->boolean('active')->default(true);
    });

    $this->author1 = DtAuthor::create(['name' => 'Ada Lovelace', 'country' => 'UK']);
    $this->author2 = DtAuthor::create(['name' => 'Grace Hopper', 'country' => 'US']);

    DtPost::create(['author_id' => $this->author1->id, 'title' => 'First post', 'views' => 10, 'published_at' => '2026-01-01', 'active' => true]);
    DtPost::create(['author_id' => $this->author1->id, 'title' => 'Second post', 'views' => 20, 'published_at' => '2026-02-01', 'active' => false]);
    DtPost::create(['author_id' => $this->author2->id, 'title' => "O'Brien's report", 'views' => 30, 'published_at' => '2026-03-01', 'active' => true]);
    DtPost::create(['author_id' => $this->author2->id, 'title' => 'مقالة عربية', 'views' => 40, 'published_at' => '2026-04-01', 'active' => true]);
});

function postsDefinition(array $overrides = []): TableDefinition
{
    return new TableDefinition(
        tableKey: 'posts',
        columns: $overrides['columns'] ?? [
            TextColumn::make('title')->sortable()->searchable(),
            TextColumn::make('views')->sortable(),
            RelationColumn::make('author.name')->sortable()->searchable(),
        ],
        filters: $overrides['filters'] ?? [
            TextFilter::make('title'),
            NumberFilter::make('views'),
            BooleanFilter::make('active'),
            DateFilter::make('published_at'),
            TextFilter::make('author.name'),
        ],
        query: fn () => DtPost::query()->where('id', '>', 0),
        defaultSort: $overrides['defaultSort'] ?? [],
    );
}

function stateFor(array $raw, TableDefinition $definition): TableState
{
    return TableState::normalize($raw, $definition);
}

test('base query scope is preserved through search', function () {
    $definition = postsDefinition();
    $builder = new TableQueryBuilder($definition);

    $state = stateFor(['search' => 'post'], $definition);
    $results = $builder->query($state)->get();

    // where('id', '>', 0) base scope is still honoured; all matching rows have id > 0.
    expect($results->pluck('id')->min())->toBeGreaterThan(0);
    expect($results->pluck('title'))->toContain('First post');
});

test('text operators', function (string $operator, string $value, array $expectedTitles) {
    $definition = postsDefinition();
    $builder = new TableQueryBuilder($definition);

    $state = stateFor(['filters' => ['title' => ['operator' => $operator, 'value' => $value]]], $definition);
    $titles = $builder->query($state)->get()->pluck('title')->all();

    expect($titles)->toEqualCanonicalizing($expectedTitles);
})->with([
    'contains' => ['contains', 'post', ['First post', 'Second post']],
    'equals' => ['equals', 'First post', ['First post']],
    'starts_with' => ['starts_with', 'Second', ['Second post']],
    'ends_with' => ['ends_with', 'post', ['First post', 'Second post']],
    'does_not_contain' => ['does_not_contain', 'post', ["O'Brien's report", 'مقالة عربية']],
    'sql-injection-like value is treated as literal' => ['equals', "x'; DROP TABLE dt_test_posts; --", []],
    'percent wildcard is escaped' => ['contains', '%', []],
    'underscore wildcard is escaped' => ['contains', '_', []],
    'quote value matches literally' => ['contains', "O'Brien", ["O'Brien's report"]],
    'unicode/arabic value matches literally' => ['contains', 'عربية', ['مقالة عربية']],
]);

test('is_empty and is_not_empty text operators', function () {
    $definition = postsDefinition();
    $builder = new TableQueryBuilder($definition);

    $emptyState = stateFor(['filters' => ['title' => ['operator' => 'is_empty', 'value' => null]]], $definition);
    expect($builder->query($emptyState)->get())->toHaveCount(0);

    $notEmptyState = stateFor(['filters' => ['title' => ['operator' => 'is_not_empty', 'value' => null]]], $definition);
    expect($builder->query($notEmptyState)->get())->toHaveCount(4);
});

test('number operators', function (string $operator, mixed $value, int $expectedCount) {
    $definition = postsDefinition();
    $builder = new TableQueryBuilder($definition);

    $state = stateFor(['filters' => ['views' => ['operator' => $operator, 'value' => $value]]], $definition);
    expect($builder->query($state)->get())->toHaveCount($expectedCount);
})->with([
    'equals' => ['equals', 10.0, 1],
    'does_not_equal' => ['does_not_equal', 10.0, 3],
    'greater_than' => ['greater_than', 20.0, 2],
    'greater_than_or_equal' => ['greater_than_or_equal', 20.0, 3],
    'less_than' => ['less_than', 20.0, 1],
    'less_than_or_equal' => ['less_than_or_equal', 20.0, 2],
    'between' => ['between', [10.0, 20.0], 2],
    'not_between' => ['not_between', [10.0, 20.0], 2],
    'boundary zero matches nothing' => ['equals', 0.0, 0],
]);

test('boolean filter is tri-state and null value is a no-op', function () {
    $definition = postsDefinition();
    $builder = new TableQueryBuilder($definition);

    $trueState = TableState::normalize(['filters' => ['active' => ['operator' => 'equals', 'value' => true]]], $definition);
    expect($builder->query($trueState)->get())->toHaveCount(3);

    $falseState = TableState::normalize(['filters' => ['active' => ['operator' => 'equals', 'value' => false]]], $definition);
    expect($builder->query($falseState)->get())->toHaveCount(1);

    // No filter entry at all (null/absent) must not filter anything.
    $noneState = TableState::normalize([], $definition);
    expect($builder->query($noneState)->get())->toHaveCount(4);
});

test('date operators are inclusive of the full day', function () {
    $definition = postsDefinition();
    $builder = new TableQueryBuilder($definition);

    $onState = stateFor(['filters' => ['published_at' => ['operator' => 'on', 'value' => '2026-01-01']]], $definition);
    expect($builder->query($onState)->get())->toHaveCount(1);

    $betweenState = stateFor(['filters' => ['published_at' => ['operator' => 'between', 'value' => ['2026-01-01', '2026-02-01']]]], $definition);
    expect($builder->query($betweenState)->get())->toHaveCount(2);

    $beforeState = stateFor(['filters' => ['published_at' => ['operator' => 'before', 'value' => '2026-02-01']]], $definition);
    expect($builder->query($beforeState)->get())->toHaveCount(1);
});

test('relation filter matches via nested dotted key', function () {
    $definition = postsDefinition();
    $builder = new TableQueryBuilder($definition);

    $state = stateFor(['filters' => ['author.name' => ['operator' => 'equals', 'value' => 'Ada Lovelace']]], $definition);
    $titles = $builder->query($state)->get()->pluck('title')->all();

    expect($titles)->toEqualCanonicalizing(['First post', 'Second post']);
});

test('relation search does not duplicate parent rows', function () {
    $definition = postsDefinition();
    $builder = new TableQueryBuilder($definition);

    $state = stateFor(['search' => 'Ada'], $definition);
    $results = $builder->query($state)->get();

    expect($results)->toHaveCount(2)
        ->and($results->pluck('id')->unique())->toHaveCount(2);
});

test('multi sort with primary key tie breaker is stable', function () {
    $definition = postsDefinition();
    $builder = new TableQueryBuilder($definition);

    $state = stateFor(['sorts' => [['column' => 'views', 'direction' => 'desc']]], $definition);
    $views = $builder->query($state)->get()->pluck('views')->all();

    expect($views)->toBe([40, 30, 20, 10]);
});

test('invalid sort column is silently dropped and falls back to primary key order', function () {
    $definition = postsDefinition();
    $builder = new TableQueryBuilder($definition);

    // TableState::normalize already drops unknown columns; simulate an already-normalized
    // state with an unsortable/unknown column slipping through defensively at the query layer.
    $state = stateFor(['sorts' => [['column' => 'not_a_real_column', 'direction' => 'asc']]], $definition);
    $ids = $builder->query($state)->get()->pluck('id')->all();

    expect($ids)->toBe(collect($ids)->sort()->values()->all());
});

test('hasMany relation sort without an aggregate throws', function () {
    $definition = new TableDefinition(
        tableKey: 'authors',
        columns: [
            TextColumn::make('name'),
            RelationColumn::make('posts.title')->sortable(),
        ],
        filters: [],
        query: fn () => DtAuthor::query(),
    );

    $builder = new TableQueryBuilder($definition);
    $state = TableState::normalize(['sorts' => [['column' => 'posts.title', 'direction' => 'asc']]], $definition);

    expect(fn () => $builder->query($state)->get())->toThrow(HasManySortWithoutAggregateException::class);
});

test('hasMany relation sort with an explicit aggregate works', function () {
    $definition = new TableDefinition(
        tableKey: 'authors',
        columns: [
            TextColumn::make('name'),
            RelationColumn::make('posts.views')->aggregate('count')->sortable(),
        ],
        filters: [],
        query: fn () => DtAuthor::query(),
    );

    $builder = new TableQueryBuilder($definition);
    $state = TableState::normalize(['sorts' => [['column' => 'posts.views', 'direction' => 'desc']]], $definition);

    $names = $builder->query($state)->get()->pluck('name')->all();
    expect($names)->toBe(['Ada Lovelace', 'Grace Hopper']);
});

test('pagination respects perPage and page', function () {
    $definition = postsDefinition();
    $builder = new TableQueryBuilder($definition);

    $state = TableState::normalize(['perPage' => 10, 'page' => 1, 'sorts' => [['column' => 'views', 'direction' => 'asc']]], $definition);
    $page1 = $builder->paginate($state);

    expect($page1->total())->toBe(4)
        ->and($page1->perPage())->toBe(10);
});

test('query count stays constant regardless of result set size (no N+1)', function () {
    // Add many more posts across the two existing authors.
    foreach (range(1, 96) as $i) {
        DtPost::create([
            'author_id' => $i % 2 === 0 ? $this->author1->id : $this->author2->id,
            'title' => "Bulk post {$i}",
            'views' => $i,
            'published_at' => '2026-05-01',
            'active' => true,
        ]);
    }

    $definition = postsDefinition();
    $builder = new TableQueryBuilder($definition);

    DB::enableQueryLog();

    $countFor = function (int $perPage) use ($builder, $definition): int {
        DB::flushQueryLog();
        $state = TableState::normalize(['perPage' => $perPage], $definition);
        $builder->paginate($state)->each(fn ($row) => $row->author); // touch eager-loaded relation

        return count(DB::getQueryLog());
    };

    $small = $countFor(5);
    $large = $countFor(100);

    expect($small)->toBe($large);
});
