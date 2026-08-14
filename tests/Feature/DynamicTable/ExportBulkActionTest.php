<?php

use App\Livewire\DynamicTable\Table;
use App\Models\User;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use Livewire\Livewire;

class BulkActionTestTable extends Table
{
    protected string $tableKey = 'bulk-action-test';

    protected ?string $model = User::class;

    protected function columns(): array
    {
        return [TextColumn::make('name')];
    }

    protected function filters(): array
    {
        return [];
    }
}

test('row selection and clear selection work correctly', function () {
    $users = User::factory()->count(3)->create();

    Livewire::test(BulkActionTestTable::class)
        ->call('selectRow', $users[0]->id)
        ->assertSet('selectedIds', [(string) $users[0]->id])
        ->call('selectPage', [$users[1]->id, $users[2]->id])
        ->assertSet('selectedIds', [(string) $users[0]->id, (string) $users[1]->id, (string) $users[2]->id])
        ->call('clearSelection')
        ->assertSet('selectedIds', [])
        ->assertSet('selectAllMatching', false);
});

test('bulk delete deletes selected records', function () {
    $users = User::factory()->count(5)->create();
    $deleteIds = [$users[0]->id, $users[1]->id];

    Livewire::test(BulkActionTestTable::class)
        ->call('selectPage', $deleteIds)
        ->call('bulkDelete');

    expect(User::whereIn('id', $deleteIds)->count())->toBe(0)
        ->and(User::count())->toBe(3);
});

test('bulk delete with selectAllMatching deletes all matching records', function () {
    User::factory()->count(5)->create();

    Livewire::test(BulkActionTestTable::class)
        ->call('toggleSelectAllMatching')
        ->call('bulkDelete');

    expect(User::count())->toBe(0);
});

test('export returns streamed download', function () {
    User::factory()->count(3)->create();

    $response = Livewire::test(BulkActionTestTable::class)
        ->call('export');

    $response->assertStatus(200);
});
