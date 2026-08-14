<?php

use App\Livewire\DynamicTable\DemoTableComponent;
use App\Models\User;
use App\Models\UserTablePreference;
use App\Support\DynamicTable\Core\TablePreferenceStore;
use Livewire\Livewire;

test('toggling a column persists one preference row for the user', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DemoTableComponent::class)
        ->call('toggleColumn', 'theme');

    $row = UserTablePreference::query()->where('user_id', $user->id)->first();

    expect($row)->not->toBeNull()
        ->and($row->preferences['hidden_columns'])->not->toContain('theme');
});

test('changing per page persists preferences with a single write', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DemoTableComponent::class)
        ->call('setPerPage', 50);

    expect(UserTablePreference::query()->where('user_id', $user->id)->count())->toBe(1)
        ->and(UserTablePreference::query()->where('user_id', $user->id)->first()->preferences['per_page'])->toBe(50);
});

test('preferences are isolated per user and never leak across users', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    Livewire::actingAs($owner)
        ->test(DemoTableComponent::class)
        ->call('setPerPage', 100);

    /** @var TablePreferenceStore $store */
    $store = app(TablePreferenceStore::class);

    expect($store->get($owner, 'demo-users')['per_page'])->toBe(100)
        ->and($store->get($other, 'demo-users'))->toBeNull();
});

test('reset preferences deletes the stored row and restores defaults', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(DemoTableComponent::class)
        ->call('setPerPage', 100);

    expect(UserTablePreference::query()->where('user_id', $user->id)->count())->toBe(1);

    $component->call('resetPreferences')
        ->assertSet('perPage', 25);

    expect(UserTablePreference::query()->where('user_id', $user->id)->count())->toBe(0);
});

test('saving preferences twice for the same user and table updates in place without duplicates', function () {
    $user = User::factory()->create();

    /** @var TablePreferenceStore $store */
    $store = app(TablePreferenceStore::class);

    $store->save($user, 'demo-users', ['version' => 1, 'hidden_columns' => [], 'column_order' => ['name'], 'per_page' => 10, 'density' => 'comfortable']);
    $store->save($user, 'demo-users', ['version' => 1, 'hidden_columns' => [], 'column_order' => ['name'], 'per_page' => 50, 'density' => 'comfortable']);

    expect(UserTablePreference::query()->where('user_id', $user->id)->where('table_key', 'demo-users')->count())->toBe(1)
        ->and($store->get($user, 'demo-users')['per_page'])->toBe(50);
});

test('a guest never triggers a preference write', function () {
    Livewire::test(DemoTableComponent::class)
        ->call('setPerPage', 50)
        ->call('toggleColumn', 'theme');

    expect(UserTablePreference::query()->count())->toBe(0);
});

test('preferences load once during mount and are not re-queried on subsequent renders', function () {
    $user = User::factory()->create();

    /** @var TablePreferenceStore $store */
    $store = app(TablePreferenceStore::class);
    $store->save($user, 'demo-users', ['version' => 1, 'hidden_columns' => [], 'column_order' => ['name', 'email', 'email_domain', 'is_verified', 'created_at', 'theme'], 'per_page' => 25, 'density' => 'comfortable']);

    User::factory()->count(3)->create();

    DB::enableQueryLog();
    $component = Livewire::actingAs($user)->test(DemoTableComponent::class);
    $mountQueries = count(DB::getQueryLog());

    DB::flushQueryLog();
    $component->call('submitSearch');
    $renderQueries = collect(DB::getQueryLog())->filter(
        fn ($q) => str_contains($q['query'], 'user_table_preferences')
    )->count();
    DB::disableQueryLog();

    expect($renderQueries)->toBe(0);
});
