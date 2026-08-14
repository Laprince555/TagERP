<?php

use App\Livewire\DynamicTable\Table;
use App\Models\User;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\TableState;
use Livewire\Livewire;

class MultiSortTestTable extends Table
{
    protected string $tableKey = 'multi-sort-test';

    protected ?string $model = User::class;

    protected function columns(): array
    {
        return [
            TextColumn::make('name')->sortable(),
            TextColumn::make('email')->sortable(),
            TextColumn::make('theme'), // not sortable
        ];
    }

    protected function filters(): array
    {
        return [];
    }
}

test('single click sort replaces the entire sort list', function () {
    $component = Livewire::test(MultiSortTestTable::class)
        ->call('sortByAdditive', 'name')
        ->call('sortByAdditive', 'email');

    expect($component->get('sorts'))->toHaveCount(2);

    $component->call('sortBy', 'email');

    // sortBy always replaces the whole list; email's direction toggles from its
    // existing 'asc' (set by the additive call above) to 'desc'.
    expect($component->get('sorts'))->toBe([['column' => 'email', 'direction' => 'desc']]);
});

test('additive sort appends a second column without discarding the first', function () {
    Livewire::test(MultiSortTestTable::class)
        ->call('sortByAdditive', 'name')
        ->call('sortByAdditive', 'email')
        ->assertSet('sorts', [
            ['column' => 'name', 'direction' => 'asc'],
            ['column' => 'email', 'direction' => 'asc'],
        ]);
});

test('additive sort on an already sorted column flips its direction in place', function () {
    Livewire::test(MultiSortTestTable::class)
        ->call('sortByAdditive', 'name')
        ->call('sortByAdditive', 'email')
        ->call('sortByAdditive', 'name')
        ->assertSet('sorts', [
            ['column' => 'name', 'direction' => 'desc'],
            ['column' => 'email', 'direction' => 'asc'],
        ]);
});

test('a non sortable column is silently ignored by additive sort', function () {
    Livewire::test(MultiSortTestTable::class)
        ->call('sortByAdditive', 'theme')
        ->assertSet('sorts', []);
});

test('removeSort drops only the targeted column', function () {
    Livewire::test(MultiSortTestTable::class)
        ->call('sortByAdditive', 'name')
        ->call('sortByAdditive', 'email')
        ->call('removeSort', 'name')
        ->assertSet('sorts', [['column' => 'email', 'direction' => 'asc']]);
});

test('resetSort restores the table default sort', function () {
    Livewire::test(MultiSortTestTable::class)
        ->call('sortByAdditive', 'name')
        ->call('sortByAdditive', 'email')
        ->call('resetSort')
        ->assertSet('sorts', []);
});

test('additive sort is capped at TableState MAX_SORTS', function () {
    $columns = [];
    for ($i = 0; $i < TableState::MAX_SORTS + 3; $i++) {
        $columns[] = TextColumn::make("f{$i}")->sortable();
    }

    $tableClass = new class extends Table
    {
        protected string $tableKey = 'multi-sort-cap-test';

        protected ?string $model = User::class;

        protected function columns(): array
        {
            $columns = [];
            for ($i = 0; $i < TableState::MAX_SORTS + 3; $i++) {
                $columns[] = TextColumn::make("f{$i}")->sortable();
            }

            return $columns;
        }

        protected function filters(): array
        {
            return [];
        }
    };

    $component = Livewire::test($tableClass::class);
    for ($i = 0; $i < TableState::MAX_SORTS + 3; $i++) {
        $component->call('sortByAdditive', "f{$i}");
    }

    expect(count($component->get('sorts')))->toBe(TableState::MAX_SORTS);
});

test('shift click multi sort wiring is present in the rendered table headers', function () {
    User::factory()->create();

    Livewire::test(MultiSortTestTable::class)
        ->assertSee('sortByAdditive', false);
});
