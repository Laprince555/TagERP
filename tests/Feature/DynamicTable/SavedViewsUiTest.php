<?php

use App\Livewire\DynamicTable\DemoTableComponent;
use App\Models\TableView;
use App\Models\User;
use Livewire\Livewire;

test('the views button and saved view names render in the toolbar', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DemoTableComponent::class)
        ->set('search', 'zed')
        ->call('submitSearch')
        ->call('saveCurrentView', 'My Filtered View')
        ->assertSee('My Filtered View')
        ->assertSee('Views');
});

test('saving with an empty name shows a validation error instead of crashing', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DemoTableComponent::class)
        ->call('saveCurrentView', '')
        ->assertSet('saveViewError', fn ($error) => $error !== null)
        ->assertOk();
});

test('applying a view sets it as the active view for display', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(DemoTableComponent::class)
        ->call('saveCurrentView', 'View A');

    $viewId = TableView::query()->where('name', 'View A')->first()->id;

    $component->call('applyView', $viewId)
        ->assertSet('activeViewId', $viewId)
        ->assertSee('View A');
});

test('deleting the active view clears the active view marker', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(DemoTableComponent::class)
        ->call('saveCurrentView', 'Temp View');

    $viewId = TableView::query()->where('name', 'Temp View')->first()->id;

    $component->call('deleteView', $viewId)
        ->assertSet('activeViewId', null);
});

test('reset to table defaults clears search filters sort and the active view marker', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(DemoTableComponent::class)
        ->set('search', 'something')
        ->call('submitSearch')
        ->call('saveCurrentView', 'Some View');

    $viewId = TableView::query()->where('name', 'Some View')->first()->id;
    $component->assertSet('activeViewId', $viewId);

    $component->call('resetToTableDefaults')
        ->assertSet('search', '')
        ->assertSet('appliedFilters', [])
        ->assertSet('activeViewId', null);
});

test('setting a view as default marks it and unmarks any previous default', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)->test(DemoTableComponent::class);
    $component->call('saveCurrentView', 'View A');
    $viewIdA = TableView::query()->where('name', 'View A')->first()->id;
    $component->call('applyView', $viewIdA)->call('setDefaultView', $viewIdA);

    $component->call('saveCurrentView', 'View B');
    $viewIdB = TableView::query()->where('name', 'View B')->first()->id;
    $component->call('applyView', $viewIdB)->call('setDefaultView', $viewIdB);

    expect(TableView::find($viewIdA)->is_default)->toBeFalse()
        ->and(TableView::find($viewIdB)->is_default)->toBeTrue();
});

test('a guest never sees the saved views ui', function () {
    $html = Livewire::test(DemoTableComponent::class)->html();

    expect($html)->not->toContain('Save current as');
});
