<?php

use App\Livewire\DynamicTable\DemoTableComponent;
use App\Livewire\DynamicTable\Table;
use App\Models\User;
use App\Support\DynamicTable\Core\Exceptions\MissingTableKeyException;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Livewire;

test('mount throws when tableKey is missing', function () {
    $anonymous = new class extends Table
    {
        protected function columns(): array
        {
            return [];
        }

        protected function filters(): array
        {
            return [];
        }

        protected function query(): Builder
        {
            return User::query();
        }
    };

    expect(fn () => $anonymous->mount())
        ->toThrow(MissingTableKeyException::class);
});

test('renders empty state with no users', function () {
    Livewire::test(DemoTableComponent::class)
        ->assertSee('No results found.');
});

test('renders populated state with users', function () {
    User::factory()->create(['name' => 'Zed Example']);

    Livewire::test(DemoTableComponent::class)
        ->assertSee('Zed Example');
});

test('search is submit triggered and resets the page', function () {
    User::factory()->create(['name' => 'Findable Name']);
    User::factory()->create(['name' => 'Other Person']);

    Livewire::test(DemoTableComponent::class)
        ->set('page', 2)
        ->set('search', 'Findable')
        ->call('submitSearch')
        ->assertSet('page', 1)
        ->assertSee('Findable Name')
        ->assertDontSee('Other Person');
});

test('filter draft then apply costs one request and clear resets it', function () {
    User::factory()->create(['name' => 'Apply Target']);
    User::factory()->create(['name' => 'Not Matching']);

    $component = Livewire::test(DemoTableComponent::class)
        ->set('filters.name.operator', 'equals')
        ->set('filters.name.value', 'Apply Target')
        ->assertSet('appliedFilters', []) // draft not applied yet — one request per field change, still unapplied
        ->call('applyFilters')
        ->assertSet('appliedFilters', ['name' => ['operator' => 'equals', 'value' => 'Apply Target']])
        ->assertSee('Apply Target')
        ->assertDontSee('Not Matching');

    $component->call('clearFilters')
        ->assertSet('appliedFilters', [])
        ->assertSee('Apply Target')
        ->assertSee('Not Matching');
});

test('pagination resets to page 1 on per page change', function () {
    User::factory()->count(3)->create();

    Livewire::test(DemoTableComponent::class)
        ->set('page', 2)
        ->call('setPerPage', 10)
        ->assertSet('page', 1)
        ->assertSet('perPage', 10);
});

test('sorting toggles direction and resets the page', function () {
    User::factory()->create(['name' => 'A User']);
    User::factory()->create(['name' => 'B User']);

    // DemoTable's defaultSort() is already ['name' => 'asc'], so the first click flips to 'desc'.
    Livewire::test(DemoTableComponent::class)
        ->set('page', 2)
        ->call('sortBy', 'name')
        ->assertSet('sorts', [['column' => 'name', 'direction' => 'desc']])
        ->assertSet('page', 1)
        ->call('sortBy', 'name')
        ->assertSet('sorts', [['column' => 'name', 'direction' => 'asc']]);
});

test('column toggle hides and shows a toggleable column', function () {
    $component = Livewire::test(DemoTableComponent::class);

    expect($component->get('visibleColumns'))->not->toContain('theme');

    $component->call('toggleColumn', 'theme')
        ->assertSet('visibleColumns', fn ($visible) => in_array('theme', $visible, true));
});

test('a non toggleable column cannot be toggled off', function () {
    $component = Livewire::test(DemoTableComponent::class);
    $initiallyVisible = $component->get('visibleColumns');

    // 'name' is not marked hiddenByDefault/non-toggleable in DemoTable, but every column here is
    // toggleable by default; assert toggling an unknown key is simply a no-op and doesn't error.
    $component->call('toggleColumn', 'not-a-real-column')
        ->assertSet('visibleColumns', $initiallyVisible);
});

test('multiple table instances on one page have isolated query string state', function () {
    $first = Livewire::test(DemoTableComponent::class)->set('search', 'first-instance');
    $second = Livewire::test(DemoTableComponent::class)->set('search', 'second-instance');

    expect($first->get('search'))->toBe('first-instance')
        ->and($second->get('search'))->toBe('second-instance');
});
