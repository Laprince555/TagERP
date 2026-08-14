<?php

use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\BooleanFilter;
use App\Support\DynamicTable\Core\Filters\EnumFilter;
use App\Support\DynamicTable\Core\Filters\NumberFilter;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\TableDefinition;
use App\Support\DynamicTable\Core\TableState;
use Illuminate\Database\Eloquent\Builder;

function makeTestDefinition(): TableDefinition
{
    return new TableDefinition(
        tableKey: 'users',
        columns: [
            TextColumn::make('name')->sortable()->searchable(),
            TextColumn::make('email')->sortable(),
            TextColumn::make('id')->toggleable(false),
            TextColumn::make('secret')->hiddenByDefault(),
        ],
        filters: [
            TextFilter::make('name'),
            NumberFilter::make('age'),
            BooleanFilter::make('active'),
            EnumFilter::make('status')->enum(StateTestStatus::class),
        ],
        query: fn () => Mockery::mock(Builder::class),
    );
}

enum StateTestStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}

test('unknown columns are dropped from sorts', function () {
    $state = TableState::normalize(['sorts' => [['column' => 'not_a_column', 'direction' => 'asc']]], makeTestDefinition());
    expect($state->sorts)->toBe([]);
});

test('non sortable columns are dropped from sorts', function () {
    $state = TableState::normalize(['sorts' => [['column' => 'secret', 'direction' => 'asc']]], makeTestDefinition());
    expect($state->sorts)->toBe([]);
});

test('invalid sort direction is dropped', function () {
    $state = TableState::normalize(['sorts' => [['column' => 'name', 'direction' => 'sideways']]], makeTestDefinition());
    expect($state->sorts)->toBe([]);
});

test('valid sort is kept', function () {
    $state = TableState::normalize(['sorts' => [['column' => 'name', 'direction' => 'desc']]], makeTestDefinition());
    expect($state->sorts)->toBe([['column' => 'name', 'direction' => 'desc']]);
});

test('per page is clamped to the allow list', function ($input, $expected) {
    $state = TableState::normalize(['perPage' => $input], makeTestDefinition());
    expect($state->perPage)->toBe($expected);
})->with([
    [25, 25],
    [999999, TableState::DEFAULT_PER_PAGE],
    ['not-a-number', TableState::DEFAULT_PER_PAGE],
    [-5, TableState::DEFAULT_PER_PAGE],
]);

test('search is trimmed and length clamped', function () {
    $state = TableState::normalize(['search' => '  '.str_repeat('a', 500).'  '], makeTestDefinition());
    expect(mb_strlen($state->search))->toBe(TableState::MAX_SEARCH_LENGTH);
});

test('non string search is coerced to empty string', function () {
    $state = TableState::normalize(['search' => ['not', 'a', 'string']], makeTestDefinition());
    expect($state->search)->toBe('');
});

test('page defaults to at least 1', function () {
    expect(TableState::normalize(['page' => -10], makeTestDefinition())->page)->toBe(1);
    expect(TableState::normalize(['page' => 0], makeTestDefinition())->page)->toBe(1);
});

test('unknown filter keys are dropped', function () {
    $state = TableState::normalize(['filters' => ['not_a_filter' => ['operator' => 'equals', 'value' => 'x']]], makeTestDefinition());
    expect($state->filters)->toBe([]);
});

test('unknown operator for a filter is dropped', function () {
    $state = TableState::normalize(['filters' => ['name' => ['operator' => 'sql_injection', 'value' => 'x']]], makeTestDefinition());
    expect($state->filters)->toBe([]);
});

test('valid text filter is kept', function () {
    $state = TableState::normalize(['filters' => ['name' => ['operator' => 'contains', 'value' => 'ali']]], makeTestDefinition());
    expect($state->filters)->toBe(['name' => ['operator' => 'contains', 'value' => 'ali']]);
});

test('number filter rejects non numeric values', function () {
    $state = TableState::normalize(['filters' => ['age' => ['operator' => 'equals', 'value' => 'abc']]], makeTestDefinition());
    expect($state->filters)->toBe([]);
});

test('boolean filter only accepts real booleans', function () {
    expect(TableState::normalize(['filters' => ['active' => ['operator' => 'equals', 'value' => 'yes']]], makeTestDefinition())->filters)->toBe([]);
    expect(TableState::normalize(['filters' => ['active' => ['operator' => 'equals', 'value' => true]]], makeTestDefinition())->filters)
        ->toBe(['active' => ['operator' => 'equals', 'value' => true]]);
});

test('boolean filter also accepts the string form an html select submits', function () {
    expect(TableState::normalize(['filters' => ['active' => ['operator' => 'equals', 'value' => '1']]], makeTestDefinition())->filters)
        ->toBe(['active' => ['operator' => 'equals', 'value' => true]]);
    expect(TableState::normalize(['filters' => ['active' => ['operator' => 'equals', 'value' => '0']]], makeTestDefinition())->filters)
        ->toBe(['active' => ['operator' => 'equals', 'value' => false]]);
    // The tri-state "Any" option submits an empty string — must mean "no filter", not false.
    expect(TableState::normalize(['filters' => ['active' => ['operator' => 'equals', 'value' => '']]], makeTestDefinition())->filters)
        ->toBe([]);
});

test('enum filter rejects stale/unknown values', function () {
    $state = TableState::normalize(['filters' => ['status' => ['operator' => 'equals', 'value' => 'deleted-enum-case']]], makeTestDefinition());
    expect($state->filters)->toBe([]);
});

test('enum filter accepts a known value', function () {
    $state = TableState::normalize(['filters' => ['status' => ['operator' => 'equals', 'value' => 'active']]], makeTestDefinition());
    expect($state->filters)->toBe(['status' => ['operator' => 'equals', 'value' => 'active']]);
});

test('non toggleable columns are always visible regardless of input', function () {
    $state = TableState::normalize(['visibleColumns' => []], makeTestDefinition());
    expect($state->visibleColumns)->toContain('id');
});

test('hidden by default columns are excluded when no explicit visibility is given', function () {
    $state = TableState::normalize([], makeTestDefinition());
    expect($state->visibleColumns)->not->toContain('secret');
});

test('column order drops unknown keys and appends missing ones', function () {
    $state = TableState::normalize(['columnOrder' => ['email', 'ghost-column']], makeTestDefinition());
    expect($state->columnOrder)->toContain('email')
        ->and($state->columnOrder)->not->toContain('ghost-column')
        ->and($state->columnOrder)->toHaveCount(4);
});
