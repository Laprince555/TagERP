<?php

use App\Livewire\DynamicTable\DemoTableComponent;
use App\Livewire\DynamicTable\Table;
use App\Models\User;
use App\Support\DynamicTable\Core\Columns\RelationColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\TableDefinition;
use App\Support\DynamicTable\Core\TableState;
use App\Support\DynamicTable\Query\TableQueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\Feature\DynamicTable\Support\DtAuthor;
use Tests\Feature\DynamicTable\Support\DtPost;

// Reuses the dt_test_authors/dt_test_posts fixture from QueryEngineTest.php.
beforeEach(function () {
    Schema::create('dt_test_authors', function ($table) {
        $table->id();
        $table->string('name');
    });

    Schema::create('dt_test_posts', function ($table) {
        $table->id();
        $table->foreignId('author_id');
        $table->string('title');
        $table->string('internal_notes')->nullable();
    });

    $this->author = DtAuthor::create(['name' => 'SECRET-AUTHOR-XYZ']);
    $this->post = DtPost::create([
        'author_id' => $this->author->id,
        'title' => 'Public Title',
        'internal_notes' => 'SECRET-VALUE-XYZ',
    ]);
});

test('searching for a value that only exists in an unauthorized direct column returns nothing', function () {
    $definition = new TableDefinition(
        tableKey: 'leak-direct',
        columns: [
            TextColumn::make('title')->searchable(),
            TextColumn::make('internal_notes')->searchable()->visible(false), // unauthorized
        ],
        filters: [],
        query: fn () => DtPost::query(),
    );

    $state = TableState::normalize(['search' => 'SECRET-VALUE-XYZ'], $definition);
    $rows = (new TableQueryBuilder($definition))->paginate($state);

    expect($rows->total())->toBe(0);
});

test('searching for a value that only exists behind an unauthorized relation column returns nothing', function () {
    $definition = new TableDefinition(
        tableKey: 'leak-relation',
        columns: [
            TextColumn::make('title')->searchable(),
            RelationColumn::make('author.name')->searchable()->visible(false), // unauthorized
        ],
        filters: [],
        query: fn () => DtPost::query(),
    );

    $state = TableState::normalize(['search' => 'SECRET-AUTHOR-XYZ'], $definition);
    $rows = (new TableQueryBuilder($definition))->paginate($state);

    expect($rows->total())->toBe(0);
});

test('a filter attached to an unauthorized column is inert even if forced into state', function () {
    $definition = new TableDefinition(
        tableKey: 'leak-filter-column',
        columns: [
            TextColumn::make('title'),
            TextColumn::make('internal_notes')->visible(false), // unauthorized
        ],
        filters: [
            TextFilter::make('internal_notes'), // targets the unauthorized column
        ],
        query: fn () => DtPost::query(),
    );

    // Attacker forces the filter into raw state directly (bypassing any UI).
    $state = TableState::normalize([
        'filters' => ['internal_notes' => ['operator' => 'contains', 'value' => 'SECRET']],
    ], $definition);

    expect($state->filters)->not->toHaveKey('internal_notes');
});

test('a filter only field can declare its own explicit visibility independent of any column', function () {
    $definition = new TableDefinition(
        tableKey: 'leak-filter-only',
        columns: [TextColumn::make('title')],
        filters: [
            TextFilter::make('internal_notes')->visible(false), // filter-only, no matching column, explicitly denied
        ],
        query: fn () => DtPost::query(),
    );

    $state = TableState::normalize([
        'filters' => ['internal_notes' => ['operator' => 'contains', 'value' => 'SECRET']],
    ], $definition);

    expect($state->filters)->not->toHaveKey('internal_notes');
});

test('a relationship filter targeting an unauthorized relation column is inert', function () {
    $definition = new TableDefinition(
        tableKey: 'leak-filter-relation',
        columns: [
            TextColumn::make('title'),
            RelationColumn::make('author.name')->visible(false),
        ],
        filters: [
            TextFilter::make('author.name'),
        ],
        query: fn () => DtPost::query(),
    );

    $state = TableState::normalize([
        'filters' => ['author.name' => ['operator' => 'contains', 'value' => 'SECRET']],
    ], $definition);

    expect($state->filters)->not->toHaveKey('author.name');
});

test('a filter tied to a column that becomes unauthorized later inherits that authorization', function () {
    $columns = [
        TextColumn::make('title'),
        TextColumn::make('internal_notes')->visible(true),
    ];
    $filters = [TextFilter::make('internal_notes')];

    $authorizedDefinition = new TableDefinition('leak-later-1', $columns, $filters, fn () => DtPost::query());
    $stateAuthorized = TableState::normalize(['filters' => ['internal_notes' => ['operator' => 'contains', 'value' => 'x']]], $authorizedDefinition);
    expect($stateAuthorized->filters)->toHaveKey('internal_notes');

    $revokedColumns = [
        TextColumn::make('title'),
        TextColumn::make('internal_notes')->visible(false),
    ];
    $revokedDefinition = new TableDefinition('leak-later-2', $revokedColumns, $filters, fn () => DtPost::query());
    $stateRevoked = TableState::normalize(['filters' => ['internal_notes' => ['operator' => 'contains', 'value' => 'x']]], $revokedDefinition);
    expect($stateRevoked->filters)->not->toHaveKey('internal_notes');
});

test('the column manager never renders an unauthorized columns label', function () {
    $user = User::factory()->create();

    $html = Livewire::actingAs($user)->test(new class extends Table
    {
        protected string $tableKey = 'leak-colmgr';

        protected function columns(): array
        {
            return [
                TextColumn::make('name')->toggleable(),
                TextColumn::make('secret_field')->toggleable()->visible(false)->label('TOTALLY-SECRET-LABEL'),
            ];
        }

        protected function filters(): array
        {
            return [];
        }

        protected function query(): Builder
        {
            return User::query();
        }
    })->html();

    expect($html)->not->toContain('TOTALLY-SECRET-LABEL');
});

test('the filter panel never renders an unauthorized filters label', function () {
    $user = User::factory()->create();

    $html = Livewire::actingAs($user)->test(new class extends Table
    {
        protected string $tableKey = 'leak-filterpanel';

        protected function columns(): array
        {
            return [TextColumn::make('name')];
        }

        protected function filters(): array
        {
            return [
                TextFilter::make('secret_filter')->visible(false)->label('TOTALLY-SECRET-FILTER-LABEL'),
            ];
        }

        protected function query(): Builder
        {
            return User::query();
        }
    })->html();

    expect($html)->not->toContain('TOTALLY-SECRET-FILTER-LABEL');
});

test('demo table smoke check still renders after authorization hardening', function () {
    Livewire::test(DemoTableComponent::class)->assertOk();
});
