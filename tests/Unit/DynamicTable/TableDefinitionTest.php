<?php

use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Exceptions\DuplicateColumnKeyException;
use App\Support\DynamicTable\Core\Exceptions\DuplicateFilterKeyException;
use App\Support\DynamicTable\Core\Exceptions\MissingTableKeyException;
use App\Support\DynamicTable\Core\Exceptions\UnknownFieldMappingException;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\DynamicTable\Core\TableDefinition;
use Illuminate\Database\Eloquent\Builder;

test('duplicate column keys throw', function () {
    expect(fn () => new TableDefinition(
        tableKey: 'users',
        columns: [TextColumn::make('name'), TextColumn::make('name')],
        filters: [],
        query: fn () => Mockery::mock(Builder::class),
    ))->toThrow(DuplicateColumnKeyException::class);
});

test('duplicate filter keys throw', function () {
    expect(fn () => new TableDefinition(
        tableKey: 'users',
        columns: [],
        filters: [TextFilter::make('name'), TextFilter::make('name')],
        query: fn () => Mockery::mock(Builder::class),
    ))->toThrow(DuplicateFilterKeyException::class);
});

test('empty table key throws', function () {
    expect(fn () => new TableDefinition(
        tableKey: '',
        columns: [],
        filters: [],
        query: fn () => Mockery::mock(Builder::class),
    ))->toThrow(MissingTableKeyException::class);
});

test('columns and filters are retrievable by key', function () {
    $definition = new TableDefinition(
        tableKey: 'users',
        columns: [TextColumn::make('name')],
        filters: [TextFilter::make('email')],
        query: fn () => Mockery::mock(Builder::class),
    );

    expect($definition->column('name'))->toBeInstanceOf(TextColumn::class)
        ->and($definition->column('missing'))->toBeNull()
        ->and($definition->filter('email'))->toBeInstanceOf(TextFilter::class);
});

test('a default sort referencing an unknown column throws at definition time', function () {
    expect(fn () => new TableDefinition(
        tableKey: 'users',
        columns: [TextColumn::make('name')->sortable()],
        filters: [],
        query: fn () => Mockery::mock(Builder::class),
        defaultSort: [Sort::make('does_not_exist')->ascending()],
    ))->toThrow(UnknownFieldMappingException::class);
});

test('a default sort referencing a real column does not throw', function () {
    $definition = new TableDefinition(
        tableKey: 'users',
        columns: [TextColumn::make('name')->sortable()],
        filters: [],
        query: fn () => Mockery::mock(Builder::class),
        defaultSort: [Sort::make('name')->ascending()],
    );

    expect($definition->defaultSort)->toHaveCount(1);
});
