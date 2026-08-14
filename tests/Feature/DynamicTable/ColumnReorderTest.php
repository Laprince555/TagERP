<?php

use App\Livewire\DynamicTable\Table;
use App\Models\User;
use App\Models\UserTablePreference;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use Livewire\Livewire;

class ReorderTestTable extends Table
{
    protected string $tableKey = 'reorder-test';

    protected ?string $model = User::class;

    protected function columns(): array
    {
        return [
            TextColumn::make('id')->toggleable(false), // fixed
            TextColumn::make('name'),
            TextColumn::make('email'),
            TextColumn::make('theme'),
        ];
    }

    protected function filters(): array
    {
        return [];
    }
}

test('dragging a column to a new position reorders it among toggleable columns', function () {
    $component = Livewire::test(ReorderTestTable::class);

    expect($component->get('columnOrder'))->toBe(['id', 'name', 'email', 'theme']);

    $component->call('sortColumns', 'theme', 0);

    expect($component->get('columnOrder'))->toBe(['id', 'theme', 'name', 'email']);
});

test('a fixed column is never included in the draggable toggleable list and cannot be reordered', function () {
    $component = Livewire::test(ReorderTestTable::class);

    // Attempting to move the fixed 'id' column via sortColumns is rejected entirely.
    $component->call('sortColumns', 'id', 2);

    expect($component->get('columnOrder')[0])->toBe('id');
});

test('the fixed column always stays first regardless of toggleable reordering', function () {
    $component = Livewire::test(ReorderTestTable::class)
        ->call('sortColumns', 'email', 0)
        ->call('sortColumns', 'theme', 0);

    expect($component->get('columnOrder')[0])->toBe('id');
});

test('an unauthorized or unknown column key injected into sortColumns is ignored', function () {
    $component = Livewire::test(ReorderTestTable::class);
    $before = $component->get('columnOrder');

    $component->call('sortColumns', 'not_a_real_column', 0);

    expect($component->get('columnOrder'))->toBe($before);
});

test('a completed reorder persists as one preference write', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ReorderTestTable::class)
        ->call('sortColumns', 'theme', 0);

    expect(UserTablePreference::query()->where('user_id', $user->id)->count())->toBe(1)
        ->and(UserTablePreference::query()->where('user_id', $user->id)->first()->preferences['column_order'])
        ->toBe(['id', 'theme', 'name', 'email']); // fixed 'id' always stored first
});

test('reordering keeps the manager html free of an unauthorized columns label', function () {
    $user = User::factory()->create();

    $html = Livewire::actingAs($user)->test(new class extends Table
    {
        protected string $tableKey = 'reorder-auth-test';

        protected ?string $model = User::class;

        protected function columns(): array
        {
            return [
                TextColumn::make('name'),
                TextColumn::make('secret')->visible(false)->label('TOTALLY-SECRET-REORDER-LABEL'),
            ];
        }

        protected function filters(): array
        {
            return [];
        }
    })->html();

    expect($html)->not->toContain('TOTALLY-SECRET-REORDER-LABEL');
});
