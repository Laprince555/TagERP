<?php

use App\Livewire\DynamicTable\DemoTableComponent;
use App\Models\User;
use App\Support\DynamicTable\Core\Columns\RelationColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\BooleanFilter;
use App\Support\DynamicTable\Core\TableDefinition;
use App\Support\DynamicTable\Core\TableState;
use App\Support\DynamicTable\Query\TableQueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\Feature\DynamicTable\Support\DtAuthor;
use Tests\Feature\DynamicTable\Support\DtPost;

// Reuses the dt_test_authors/dt_test_posts fixture and postsDefinition()/stateFor() helpers
// declared in QueryEngineTest.php (same test suite, autoloaded by Pest).
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
    DtPost::create(['author_id' => $this->author1->id, 'title' => 'Public Post', 'views' => 10, 'active' => true]);
});

test('a column with visible(false) never appears in the compiled SQL select even if forced into state', function () {
    $definition = new TableDefinition(
        tableKey: 'posts-auth',
        columns: [
            TextColumn::make('title')->sortable()->searchable(),
            TextColumn::make('views')->visible(false), // unauthorized: e.g. a salary/cost field
        ],
        filters: [],
        query: fn () => DtPost::query(),
    );

    // Attacker forces the unauthorized column key into visibleColumns/columnOrder — the exact
    // shape a tampered Livewire request or query-string payload would send.
    $state = TableState::normalize([
        'visibleColumns' => ['title', 'views'],
        'columnOrder' => ['title', 'views'],
    ], $definition);

    DB::enableQueryLog();
    (new TableQueryBuilder($definition))->paginate($state);
    $selectQueries = collect(DB::getQueryLog())->pluck('query')->filter(fn ($q) => str_starts_with(trim($q), 'select'));
    DB::disableQueryLog();

    expect($state->visibleColumns)->not->toContain('views')
        ->and($selectQueries->contains(fn ($q) => str_contains($q, '"views"') || str_contains($q, '`views`')))->toBeFalse();
});

test('forcing an unauthorized column into a sort request is ignored', function () {
    $definition = new TableDefinition(
        tableKey: 'posts-auth-sort',
        columns: [
            TextColumn::make('title')->sortable(),
            TextColumn::make('views')->visible(false)->sortable(),
        ],
        filters: [],
        query: fn () => DtPost::query(),
    );

    $state = TableState::normalize([
        'sorts' => [['column' => 'views', 'direction' => 'asc']],
    ], $definition);

    expect($state->sorts)->toBe([]);
});

test('a hidden relation column is not eager loaded', function () {
    $definition = new TableDefinition(
        tableKey: 'posts-hidden-relation',
        columns: [
            TextColumn::make('title')->sortable(),
            RelationColumn::make('author.name')->visible(false),
        ],
        filters: [],
        query: fn () => DtPost::query(),
    );

    $state = TableState::normalize(['visibleColumns' => ['title'], 'columnOrder' => ['title']], $definition);

    DB::enableQueryLog();
    (new TableQueryBuilder($definition))->paginate($state);
    $touchedAuthorsTable = collect(DB::getQueryLog())->pluck('query')->contains(fn ($q) => str_contains($q, 'dt_test_authors'));
    DB::disableQueryLog();

    expect($touchedAuthorsTable)->toBeFalse();
});

test('sql injection payloads in search never produce a database error or extra rows', function () {
    $definition = new TableDefinition(
        tableKey: 'posts-sqli',
        columns: [TextColumn::make('title')->searchable()],
        filters: [],
        query: fn () => DtPost::query(),
    );

    $payloads = [
        "' OR '1'='1",
        "'; DROP TABLE dt_test_posts; --",
        "1' UNION SELECT null,null,null,null,null--",
        "%' OR 1=1 -- ",
    ];

    foreach ($payloads as $payload) {
        $state = TableState::normalize(['search' => $payload], $definition);
        $rows = (new TableQueryBuilder($definition))->paginate($state);

        expect($rows->total())->toBe(0); // no row literally contains the payload text
    }

    // The table must still exist and be queryable after every payload.
    expect(DtPost::count())->toBe(1);
});

test('xss payloads in a column value are escaped in rendered html', function () {
    $user = User::factory()->create(['name' => '<script>alert(1)</script>']);

    Livewire::actingAs($user)
        ->test(DemoTableComponent::class)
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;script&gt;', false);
});

test('a stale enum value submitted as a filter is dropped rather than queried', function () {
    $definition = new TableDefinition(
        tableKey: 'posts-stale-enum',
        columns: [TextColumn::make('title')],
        filters: [BooleanFilter::make('active')],
        query: fn () => DtPost::query(),
    );

    // A boolean filter only ever accepts true/false; anything else (e.g. a tampered enum-like
    // string) is dropped by TableState::normalize rather than reaching the query.
    $state = TableState::normalize(['filters' => ['active' => ['operator' => 'equals', 'value' => 'not-a-real-value']]], $definition);

    expect($state->filters)->not->toHaveKey('active');
});

test('a tampered per page value outside the allowlist falls back to the default', function () {
    $definition = new TableDefinition(
        tableKey: 'posts-perpage',
        columns: [TextColumn::make('title')],
        filters: [],
        query: fn () => DtPost::query(),
    );

    $state = TableState::normalize(['perPage' => 999999], $definition);

    expect($state->perPage)->toBe(TableState::DEFAULT_PER_PAGE);
});

test('a link callback returning a javascript scheme is rejected server side', function () {
    $column = TextColumn::make('title')->link(fn () => 'javascript:alert(1)');

    expect($column->getLink(null))->toBeNull();
});

test('a link callback returning a normal https url is preserved', function () {
    $column = TextColumn::make('title')->link(fn () => 'https://example.com/path');

    expect($column->getLink(null))->toBe('https://example.com/path');
});

test('a link callback returning a root relative path is preserved', function () {
    $column = TextColumn::make('title')->link(fn () => '/posts/1');

    expect($column->getLink(null))->toBe('/posts/1');
});
