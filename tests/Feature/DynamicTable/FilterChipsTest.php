<?php

use App\Livewire\DynamicTable\DemoTableComponent;
use App\Models\User;
use Livewire\Livewire;

test('an applied filter renders as a chip with its label and value', function () {
    User::factory()->create(['name' => 'Findable']);

    Livewire::test(DemoTableComponent::class)
        ->set('filters.name.operator', 'equals')
        ->set('filters.name.value', 'Findable')
        ->call('applyFilters')
        ->assertSee('Findable', false);
});

test('a draft filter that has not been applied does not render a chip', function () {
    Livewire::test(DemoTableComponent::class)
        ->set('filters.name.operator', 'equals')
        ->set('filters.name.value', 'Not Yet Applied')
        ->assertSet('appliedFilters', [])
        ->assertDontSee('Not Yet Applied');
});

test('unapplied changes indicator shows only when the draft differs from applied', function () {
    $component = Livewire::test(DemoTableComponent::class);

    // No draft changes yet.
    $html = $component->html();
    expect($html)->not->toContain('Unapplied changes');

    $component->set('filters.name.operator', 'equals')->set('filters.name.value', 'x');
    $html = $component->html();
    expect($html)->toContain('Unapplied changes');

    $component->call('applyFilters');
    $html = $component->html();
    expect($html)->not->toContain('Unapplied changes');
});

test('clearFilter removes only the targeted filter chip, leaving other applied filters intact', function () {
    $component = Livewire::test(DemoTableComponent::class)
        ->set('filters.name.operator', 'equals')
        ->set('filters.name.value', 'Alice')
        ->set('filters.email.operator', 'contains')
        ->set('filters.email.value', 'example.com')
        ->call('applyFilters');

    expect($component->get('appliedFilters'))->toHaveKeys(['name', 'email']);

    $component->call('clearFilter', 'name');

    expect($component->get('appliedFilters'))->not->toHaveKey('name')
        ->and($component->get('appliedFilters'))->toHaveKey('email');
});

test('clearFilter resets pagination to page 1', function () {
    User::factory()->count(30)->create();

    Livewire::test(DemoTableComponent::class)
        ->set('filters.name.operator', 'equals')
        ->set('filters.name.value', 'x')
        ->call('applyFilters')
        ->set('page', 2)
        ->call('clearFilter', 'name')
        ->assertSet('page', 1);
});
