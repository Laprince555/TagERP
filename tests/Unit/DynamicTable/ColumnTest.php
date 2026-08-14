<?php

use App\Models\User;
use App\Support\DynamicTable\Core\Column;
use App\Support\DynamicTable\Core\Columns\ComputedColumn;
use App\Support\DynamicTable\Core\Columns\EnumColumn;
use App\Support\DynamicTable\Core\Columns\RelationColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Exceptions\FilterTargetUnavailableException;
use App\Support\DynamicTable\Core\Exceptions\InvalidEnumConfigurationException;
use App\Support\DynamicTable\Core\Exceptions\SortableComputedColumnWithoutDataSourceException;
use App\Support\DynamicTable\Core\Exceptions\UnsupportedRelationPathException;
use App\Support\DynamicTable\Core\TableDefinition;
use Illuminate\Database\Eloquent\Builder;

test('fluent methods return the same instance for chaining', function () {
    $column = TextColumn::make('name')
        ->label('Name')
        ->sortable()
        ->searchable()
        ->hiddenByDefault()
        ->toggleable(false)
        ->placeholder('-')
        ->align('left')
        ->width('200px')
        ->exportable(false);

    expect($column)->toBeInstanceOf(TextColumn::class)
        ->and($column->getKey())->toBe('name')
        ->and($column->getLabel())->toBe('Name')
        ->and($column->isSortable())->toBeTrue()
        ->and($column->isSearchable())->toBeTrue()
        ->and($column->isHiddenByDefault())->toBeTrue()
        ->and($column->isToggleable())->toBeFalse()
        ->and($column->getPlaceholder())->toBe('-')
        ->and($column->getAlign())->toBe('left')
        ->and($column->getWidth())->toBe('200px')
        ->and($column->isExportable())->toBeFalse();
});

test('label defaults to a headline of the key', function () {
    expect(TextColumn::make('first_name')->getLabel())->toBe('First Name');
});

test('field defaults to the key unless overridden', function () {
    $column = TextColumn::make('full_name');
    expect($column->getField())->toBe('full_name');

    $column->field('name');
    expect($column->getField())->toBe('name');
});

test('visible supports both bool and callable', function () {
    expect(TextColumn::make('x')->visible(false)->isVisible())->toBeFalse()
        ->and(TextColumn::make('x')->visible(fn () => true)->isVisible())->toBeTrue();
});

function definitionWith(Column $column): TableDefinition
{
    return new TableDefinition(
        tableKey: 'computed-column-test',
        columns: [$column],
        filters: [],
        query: fn (): Builder => User::query(),
    );
}

test('computed column rejects sortable without a declared field', function () {
    $column = ComputedColumn::make('full_name')->sortable(); // fluent call alone does not throw

    expect(fn () => definitionWith($column))
        ->toThrow(SortableComputedColumnWithoutDataSourceException::class);
});

test('computed column rejects searchable without a declared field', function () {
    $column = ComputedColumn::make('full_name')->searchable();

    expect(fn () => definitionWith($column))
        ->toThrow(FilterTargetUnavailableException::class);
});

test('computed column allows sortable and searchable once a field is declared, field first', function () {
    $column = ComputedColumn::make('full_name')->field('name')->sortable()->searchable();

    definitionWith($column); // must not throw

    expect($column->isSortable())->toBeTrue()
        ->and($column->isSearchable())->toBeTrue();
});

test('computed column allows sortable and searchable once a field is declared, field last', function () {
    // Same fluent calls, reversed order — proves the guard is no longer call-order dependent.
    $column = ComputedColumn::make('full_name')->sortable()->searchable()->field('name');

    definitionWith($column); // must not throw

    expect($column->isSortable())->toBeTrue()
        ->and($column->isSearchable())->toBeTrue();
});

test('relation column requires a dotted key', function () {
    expect(fn () => RelationColumn::make('country'))
        ->toThrow(UnsupportedRelationPathException::class);
});

test('relation column splits relation path and field', function () {
    $column = RelationColumn::make('country.region.name');

    expect($column->getRelationPath())->toBe('country.region')
        ->and($column->getRelationField())->toBe('name');
});

test('enum column rejects non backed enum classes', function () {
    expect(fn () => EnumColumn::make('status')->enum(NonBackedEnumForTest::class))
        ->toThrow(InvalidEnumConfigurationException::class);
});

test('enum column accepts a backed enum class', function () {
    $column = EnumColumn::make('status')->enum(BackedEnumForTest::class);

    expect($column->formatValue(BackedEnumForTest::Active->value, null))->toBe('Active');
});

enum NonBackedEnumForTest
{
    case A;
}

enum BackedEnumForTest: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
