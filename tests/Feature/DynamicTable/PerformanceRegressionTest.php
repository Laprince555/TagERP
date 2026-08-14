<?php

use App\Support\DynamicTable\Core\Columns\TextColumn;
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
    });

    Schema::create('dt_test_posts', function ($table) {
        $table->id();
        $table->foreignId('author_id');
        $table->string('title');
    });

    $author = DtAuthor::create(['name' => 'Ada Lovelace']);
    for ($i = 0; $i < 10; $i++) {
        DtPost::create(['author_id' => $author->id, 'title' => "Post {$i}"]);
    }
});

function perfDefinition(): TableDefinition
{
    return new TableDefinition(
        tableKey: 'perf-posts',
        columns: [TextColumn::make('title')->sortable()],
        filters: [],
        query: fn () => DtPost::query(),
    );
}

test('simple pagination never issues a count query', function () {
    $definition = perfDefinition();
    $state = TableState::normalize([], $definition);

    DB::enableQueryLog();
    (new TableQueryBuilder($definition))->simplePaginate($state);
    $hasCountQuery = collect(DB::getQueryLog())->pluck('query')->contains(fn ($q) => str_contains($q, 'count('));
    DB::disableQueryLog();

    expect($hasCountQuery)->toBeFalse();
});

test('standard pagination issues exactly one count query', function () {
    $definition = perfDefinition();
    $state = TableState::normalize([], $definition);

    DB::enableQueryLog();
    (new TableQueryBuilder($definition))->paginate($state);
    $countQueries = collect(DB::getQueryLog())->pluck('query')->filter(fn ($q) => str_contains($q, 'count('));
    DB::disableQueryLog();

    expect($countQueries)->toHaveCount(1);
});
