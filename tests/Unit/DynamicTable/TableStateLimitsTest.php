<?php

use App\Models\User;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\TableDefinition;
use App\Support\DynamicTable\Core\TableState;
use Illuminate\Database\Eloquent\Builder;

function limitsDefinition(): TableDefinition
{
    return new TableDefinition(
        tableKey: 'limits-test',
        columns: [
            TextColumn::make('a')->sortable(),
            TextColumn::make('b')->sortable(),
            TextColumn::make('c')->sortable(),
        ],
        filters: [
            TextFilter::make('a'),
            TextFilter::make('b'),
            TextFilter::make('c'),
        ],
        query: fn (): Builder => User::query(),
    );
}

test('duplicate column order entries are deduplicated', function () {
    $state = TableState::normalize(['columnOrder' => ['a', 'a', 'b', 'a']], limitsDefinition());

    expect($state->columnOrder)->toBe(['a', 'b', 'c']);
});

test('duplicate visible column entries are deduplicated', function () {
    $state = TableState::normalize(['visibleColumns' => ['a', 'a', 'a']], limitsDefinition());

    expect($state->visibleColumns)->toBe(['a']);
});

test('a column appearing twice in sorts keeps only the first, higher priority entry', function () {
    $state = TableState::normalize([
        'sorts' => [
            ['column' => 'a', 'direction' => 'asc'],
            ['column' => 'a', 'direction' => 'desc'],
        ],
    ], limitsDefinition());

    expect($state->sorts)->toBe([['column' => 'a', 'direction' => 'asc']]);
});

test('sorts are capped at MAX_SORTS entries', function () {
    $sorts = [];
    for ($i = 0; $i < TableState::MAX_SORTS + 10; $i++) {
        $sorts[] = ['column' => ['a', 'b', 'c'][$i % 3], 'direction' => 'asc'];
    }

    $state = TableState::normalize(['sorts' => $sorts], limitsDefinition());

    expect(count($state->sorts))->toBeLessThanOrEqual(TableState::MAX_SORTS);
});

test('filters are capped at MAX_FILTERS entries', function () {
    // Only 3 real filters exist in this definition, so cap enforcement is proven with a
    // definition that has more — reuse the multiplier pattern via distinct filter keys.
    $columns = [];
    $filters = [];
    for ($i = 0; $i < TableState::MAX_FILTERS + 10; $i++) {
        $columns[] = TextColumn::make("f{$i}");
        $filters[] = TextFilter::make("f{$i}");
    }

    $definition = new TableDefinition('limits-many-filters', $columns, $filters, fn (): Builder => User::query());

    $raw = [];
    for ($i = 0; $i < TableState::MAX_FILTERS + 10; $i++) {
        $raw["f{$i}"] = ['operator' => 'equals', 'value' => 'x'];
    }

    $state = TableState::normalize(['filters' => $raw], $definition);

    expect(count($state->filters))->toBeLessThanOrEqual(TableState::MAX_FILTERS);
});

test('an excessively large page number is clamped to MAX_PAGE', function () {
    $state = TableState::normalize(['page' => 999999999], limitsDefinition());

    expect($state->page)->toBe(TableState::MAX_PAGE);
});
