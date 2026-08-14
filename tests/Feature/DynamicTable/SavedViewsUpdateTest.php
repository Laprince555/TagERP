<?php

use App\Livewire\DynamicTable\Table;
use App\Models\User;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\SavedTableViewStore;
use App\Support\DynamicTable\PreferenceStores\EloquentSavedTableViewStore;
use Livewire\Livewire;

class SavedViewTestTable extends Table
{
    protected string $tableKey = 'saved-view-test';

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

test('user can rename and update their own saved view', function () {
    $user = User::factory()->create();
    $store = new EloquentSavedTableViewStore;

    $viewId = $store->create($user, 'saved-view-test', 'Old Name', ['search' => 'foo']);

    $store->rename($user, 'saved-view-test', $viewId, 'New Name');
    $view = $store->find($user, 'saved-view-test', $viewId);

    expect($view['name'])->toBe('New Name');

    $store->update($user, 'saved-view-test', $viewId, ['search' => 'bar']);
    $view = $store->find($user, 'saved-view-test', $viewId);

    expect($view['configuration']['search'])->toBe('bar');
});

test('user cannot rename or update another users saved view', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $store = new EloquentSavedTableViewStore;

    $viewId = $store->create($user1, 'saved-view-test', 'User 1 View', ['search' => 'foo']);

    $store->rename($user2, 'saved-view-test', $viewId, 'Hacked Name');
    $view = $store->find($user1, 'saved-view-test', $viewId);

    expect($view['name'])->toBe('User 1 View');

    $store->update($user2, 'saved-view-test', $viewId, ['search' => 'hacked']);
    $view = $store->find($user1, 'saved-view-test', $viewId);

    expect($view['configuration']['search'])->toBe('foo');
});

test('livewire component can call renameView and updateView', function () {
    $user = User::factory()->create();
    $store = app(SavedTableViewStore::class);
    $viewId = $store->create($user, 'saved-view-test', 'Initial Name', ['search' => 'initial']);

    Livewire::actingAs($user)
        ->test(SavedViewTestTable::class)
        ->set('search', 'updated search')
        ->call('updateView', $viewId)
        ->call('renameView', $viewId, 'Renamed View');

    $view = $store->find($user, 'saved-view-test', $viewId);

    expect($view['name'])->toBe('Renamed View')
        ->and($view['configuration']['search'])->toBe('updated search');
});
