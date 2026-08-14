<?php

use App\Models\User;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\TableDefinition;
use App\Support\DynamicTable\Core\TablePreferences;
use Illuminate\Database\Eloquent\Builder;

function makeDefinition(array $columns, string $key = 'prefs-test'): TableDefinition
{
    return new TableDefinition(
        tableKey: $key,
        columns: $columns,
        filters: [],
        query: fn (): Builder => User::query(),
    );
}

test('normalize seeds hidden-by-default columns when no stored prefs exist', function () {
    $definition = makeDefinition([
        TextColumn::make('name'),
        TextColumn::make('secret')->hiddenByDefault(),
    ]);

    $prefs = TablePreferences::normalize($definition, null);

    expect($prefs->hiddenColumns)->toContain('secret')
        ->and($prefs->visibleColumns())->toContain('name')
        ->and($prefs->visibleColumns())->not->toContain('secret');
});

test('normalize adds a newly defined column in definition order', function () {
    $definition = makeDefinition([
        TextColumn::make('name'),
        TextColumn::make('email'),
    ]);

    $stored = TablePreferences::normalize($definition, null)->toArray();

    // Simulate a new column added to the definition after the user's prefs were saved.
    $definitionWithNewColumn = makeDefinition([
        TextColumn::make('name'),
        TextColumn::make('email'),
        TextColumn::make('phone'),
    ]);

    $prefs = TablePreferences::normalize($definitionWithNewColumn, $stored);

    expect($prefs->columnOrder)->toContain('phone')
        ->and($prefs->visibleColumns())->toContain('phone');
});

test('normalize drops a removed column from stored preferences', function () {
    $definition = makeDefinition([
        TextColumn::make('name'),
        TextColumn::make('email'),
    ]);

    $stored = TablePreferences::normalize($definition, null)->toArray();

    $definitionWithoutEmail = makeDefinition([
        TextColumn::make('name'),
    ]);

    $prefs = TablePreferences::normalize($definitionWithoutEmail, $stored);

    expect($prefs->columnOrder)->not->toContain('email')
        ->and($prefs->hiddenColumns)->not->toContain('email');
});

test('normalize removes a column whose authorization was revoked', function () {
    $definition = makeDefinition([
        TextColumn::make('name'),
        TextColumn::make('salary')->visible(true),
    ]);

    $stored = TablePreferences::normalize($definition, null)->toArray();

    $definitionRevoked = makeDefinition([
        TextColumn::make('name'),
        TextColumn::make('salary')->visible(false),
    ]);

    $prefs = TablePreferences::normalize($definitionRevoked, $stored);

    expect($prefs->columnOrder)->not->toContain('salary')
        ->and($prefs->visibleColumns())->not->toContain('salary');
});

test('normalize keeps a fixed non toggleable column in position and never hides it', function () {
    $definition = makeDefinition([
        TextColumn::make('id')->toggleable(false),
        TextColumn::make('name'),
    ]);

    $raw = ['version' => 1, 'hidden_columns' => ['id'], 'column_order' => ['name', 'id'], 'per_page' => 25, 'density' => 'comfortable'];

    $prefs = TablePreferences::normalize($definition, $raw);

    expect($prefs->columnOrder[0])->toBe('id')
        ->and($prefs->hiddenColumns)->not->toContain('id');
});

test('normalize deduplicates column order and clamps per page to the allowlist', function () {
    $definition = makeDefinition([
        TextColumn::make('name'),
    ]);

    $raw = ['version' => 1, 'hidden_columns' => [], 'column_order' => ['name', 'name', 'name'], 'per_page' => 99999, 'density' => 'comfortable'];

    $prefs = TablePreferences::normalize($definition, $raw);

    expect($prefs->columnOrder)->toBe(['name'])
        ->and($prefs->perPage)->toBeLessThanOrEqual(100);
});

test('normalize handles empty preferences without error', function () {
    $definition = makeDefinition([TextColumn::make('name')]);

    $prefs = TablePreferences::normalize($definition, []);

    expect($prefs->columnOrder)->toBe(['name']);
});

test('normalize resets an old schema version to the current version', function () {
    $definition = makeDefinition([TextColumn::make('name')]);

    $stale = ['version' => 0, 'hidden_columns' => [], 'column_order' => ['name'], 'per_page' => 25, 'density' => 'comfortable'];

    $prefs = TablePreferences::normalize($definition, $stale);

    expect($prefs->schemaVersion)->toBe(1);
});

test('normalize rejects an invalid density value', function () {
    $definition = makeDefinition([TextColumn::make('name')]);

    $raw = ['version' => 1, 'hidden_columns' => [], 'column_order' => ['name'], 'per_page' => 25, 'density' => 'javascript:alert(1)'];

    $prefs = TablePreferences::normalize($definition, $raw);

    expect($prefs->density)->toBe('comfortable');
});
