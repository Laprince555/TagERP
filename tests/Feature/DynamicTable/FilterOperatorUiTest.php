<?php

use App\Livewire\DynamicTable\Table;
use App\Models\User;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\DateFilter;
use App\Support\DynamicTable\Core\Filters\NumberFilter;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use Livewire\Livewire;

function filterUiTableClass(): string
{
    return (new class extends Table
    {
        protected string $tableKey = 'filter-ui-test';

        protected ?string $model = User::class;

        protected function columns(): array
        {
            return [TextColumn::make('name')->sortable()];
        }

        protected function filters(): array
        {
            return [
                NumberFilter::make('id'),
                DateFilter::make('created_at'),
                TextFilter::make('name'),
            ];
        }
    })::class;
}

test('a number filter with the between operator narrows results using both bounds', function () {
    $u1 = User::factory()->create();
    $u2 = User::factory()->create();
    $u3 = User::factory()->create();

    Livewire::test(filterUiTableClass())
        ->set('filters.id.operator', 'between')
        ->set('filters.id.value', [$u1->id, $u2->id])
        ->call('applyFilters')
        ->assertSee($u1->name)
        ->assertSee($u2->name)
        ->assertDontSee($u3->name);
});

test('a number filter with the not_between operator excludes the bounded range', function () {
    $u1 = User::factory()->create();
    $u2 = User::factory()->create();
    $u3 = User::factory()->create();

    Livewire::test(filterUiTableClass())
        ->set('filters.id.operator', 'not_between')
        ->set('filters.id.value', [$u1->id, $u2->id])
        ->call('applyFilters')
        ->assertDontSee($u1->name)
        ->assertDontSee($u2->name)
        ->assertSee($u3->name);
});

test('a date filter with the between operator is inclusive of both end of day boundaries', function () {
    $inRange = User::factory()->create(['created_at' => '2026-01-15 23:59:00']);
    $outOfRange = User::factory()->create(['created_at' => '2026-02-01 00:00:00']);

    Livewire::test(filterUiTableClass())
        ->set('filters.created_at.operator', 'between')
        ->set('filters.created_at.value', ['2026-01-01', '2026-01-15'])
        ->call('applyFilters')
        ->assertSee($inRange->name)
        ->assertDontSee($outOfRange->name);
});

test('an is_empty operator requires no value to be submitted', function () {
    User::factory()->create(['name' => '']);
    $named = User::factory()->create(['name' => 'Has A Name']);

    Livewire::test(filterUiTableClass())
        ->set('filters.name.operator', 'is_empty')
        ->call('applyFilters')
        ->assertSet('appliedFilters', ['name' => ['operator' => 'is_empty']])
        ->assertDontSee($named->name);
});

test('number and date filter panel controls entangle the operator for client side value toggling', function () {
    $html = Livewire::test(filterUiTableClass())->html();

    // Structural proof the between/no-value UI wiring is present per filter — actual
    // show/hide behavior is Alpine (client-side), not observable from Livewire::test().
    expect($html)->toContain("entangle('filters.id.operator')")
        ->and($html)->toContain("entangle('filters.created_at.operator')")
        ->and($html)->toContain("entangle('filters.name.operator')");
});
