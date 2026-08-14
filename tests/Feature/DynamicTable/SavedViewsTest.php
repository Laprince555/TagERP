<?php

use App\Livewire\DynamicTable\DemoTableComponent;
use App\Models\TableView;
use App\Models\User;
use App\Support\DynamicTable\Core\SavedTableViewStore;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

test('a saved view can be created updated and listed', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(DemoTableComponent::class)
        ->set('search', 'zed')
        ->call('submitSearch')
        ->call('saveCurrentView', 'My View');

    expect($component->get('savedViews'))->toHaveCount(1)
        ->and($component->get('savedViews')[0]['name'])->toBe('My View');

    // Saving with the same name updates in place, no duplicate row.
    $component->call('saveCurrentView', 'My View');

    expect(TableView::query()->where('user_id', $user->id)->count())->toBe(1);
});

test('applying a view overlays its filters search sort and column state and resets the page', function () {
    $user = User::factory()->create();
    User::factory()->create(['name' => 'Findable']);
    User::factory()->create(['name' => 'Other']);

    $component = Livewire::actingAs($user)->test(DemoTableComponent::class);

    $component->set('search', 'Findable')
        ->call('submitSearch')
        ->call('toggleColumn', 'theme')
        ->call('saveCurrentView', 'Search View');

    // Reset live state to something else, then re-apply the saved view.
    $component->set('search', '')->set('page', 2);

    $viewId = TableView::query()->where('name', 'Search View')->first()->id;

    $component->call('applyView', $viewId)
        ->assertSet('search', 'Findable')
        ->assertSet('page', 1)
        ->assertSee('Findable')
        ->assertDontSee('Other');
});

test('only one default view exists per user and table at a time', function () {
    $user = User::factory()->create();

    /** @var SavedTableViewStore $store */
    $store = app(SavedTableViewStore::class);
    $idA = $store->create($user, 'demo-users', 'View A', ['search' => '', 'filters' => [], 'sorts' => [], 'page' => 1, 'perPage' => 25, 'visibleColumns' => [], 'columnOrder' => []]);
    $idB = $store->create($user, 'demo-users', 'View B', ['search' => '', 'filters' => [], 'sorts' => [], 'page' => 1, 'perPage' => 25, 'visibleColumns' => [], 'columnOrder' => []]);

    $store->setDefault($user, 'demo-users', $idA);
    $store->setDefault($user, 'demo-users', $idB);

    expect(TableView::find($idA)->is_default)->toBeFalse()
        ->and(TableView::find($idB)->is_default)->toBeTrue();
});

test('a default view is applied automatically when the table mounts', function () {
    $user = User::factory()->create();
    User::factory()->create(['name' => 'Default View User']);
    User::factory()->create(['name' => 'Someone Else']);

    /** @var SavedTableViewStore $store */
    $store = app(SavedTableViewStore::class);
    $viewId = $store->create($user, 'demo-users', 'Default', ['search' => 'Default View', 'filters' => [], 'sorts' => [], 'page' => 1, 'perPage' => 25, 'visibleColumns' => ['name', 'email'], 'columnOrder' => ['name', 'email']]);
    $store->setDefault($user, 'demo-users', $viewId);

    Livewire::actingAs($user)
        ->test(DemoTableComponent::class)
        ->assertSet('search', 'Default View')
        ->assertSee('Default View User')
        ->assertDontSee('Someone Else');
});

test('deleting a saved view removes it and no longer applies on mount', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)->test(DemoTableComponent::class)
        ->call('saveCurrentView', 'Temp View');

    $viewId = TableView::query()->where('name', 'Temp View')->first()->id;

    $component->call('deleteView', $viewId);

    expect(TableView::find($viewId))->toBeNull()
        ->and($component->get('savedViews'))->toBe([]);
});

test('deleting the default view means the table falls back to its own default next mount', function () {
    $user = User::factory()->create();

    /** @var SavedTableViewStore $store */
    $store = app(SavedTableViewStore::class);
    $viewId = $store->create($user, 'demo-users', 'Default', ['search' => 'zzz-no-match', 'filters' => [], 'sorts' => [], 'page' => 1, 'perPage' => 25, 'visibleColumns' => [], 'columnOrder' => []]);
    $store->setDefault($user, 'demo-users', $viewId);
    $store->delete($user, 'demo-users', $viewId);

    Livewire::actingAs($user)
        ->test(DemoTableComponent::class)
        ->assertSet('search', '');
});

test('a stale view referencing a since removed filter is normalized rather than crashing', function () {
    $user = User::factory()->create();

    /** @var SavedTableViewStore $store */
    $store = app(SavedTableViewStore::class);
    $viewId = $store->create($user, 'demo-users', 'Stale', [
        'search' => '',
        'filters' => ['no_longer_exists' => ['operator' => 'equals', 'value' => 'x']],
        'sorts' => [['column' => 'removed_column', 'direction' => 'asc']],
        'page' => 1,
        'perPage' => 25,
        'visibleColumns' => ['name'],
        'columnOrder' => ['name'],
    ]);

    Livewire::actingAs($user)
        ->test(DemoTableComponent::class)
        ->call('applyView', $viewId)
        ->assertOk();
});

test('a user cannot apply delete or default another users saved view', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();

    /** @var SavedTableViewStore $store */
    $store = app(SavedTableViewStore::class);
    $viewId = $store->create($owner, 'demo-users', 'Private View', ['search' => 'owner-only', 'filters' => [], 'sorts' => [], 'page' => 1, 'perPage' => 25, 'visibleColumns' => [], 'columnOrder' => []]);

    $component = Livewire::actingAs($attacker)->test(DemoTableComponent::class);

    // Attempting to apply/delete/default someone else's view id is a silent no-op — never leaks or mutates it.
    $component->call('applyView', $viewId)->assertSet('search', '');
    $component->call('deleteView', $viewId);
    $component->call('setDefaultView', $viewId);

    expect(TableView::find($viewId))->not->toBeNull()
        ->and(TableView::find($viewId)->user_id)->toBe($owner->id)
        ->and(TableView::find($viewId)->is_default)->toBeFalse();
});

test('an oversized saved view configuration is rejected rather than stored', function () {
    $user = User::factory()->create();

    /** @var SavedTableViewStore $store */
    $store = app(SavedTableViewStore::class);

    $oversized = ['search' => str_repeat('x', 30_000), 'filters' => [], 'sorts' => [], 'page' => 1, 'perPage' => 25, 'visibleColumns' => [], 'columnOrder' => []];

    expect(fn () => $store->create($user, 'demo-users', 'Too Big', $oversized))
        ->toThrow(ValidationException::class);
});
