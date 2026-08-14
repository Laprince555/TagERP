<?php

use App\Livewire\DynamicTable\Table;
use App\Models\User;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use Livewire\Livewire;

class OrderRenderTestTable extends Table
{
    protected string $tableKey = 'order-render-test';

    protected ?string $model = User::class;

    protected function columns(): array
    {
        return [
            TextColumn::make('id')->toggleable(false)->label('ID Column'), // fixed
            TextColumn::make('name')->label('Name Column'),
            TextColumn::make('email')->label('Email Column'),
            TextColumn::make('theme')->label('Theme Column'),
        ];
    }

    protected function filters(): array
    {
        return [];
    }
}

test('dragging a column to a new position changes the actual rendered header order', function () {
    User::factory()->create();

    $component = Livewire::test(OrderRenderTestTable::class);

    // Definition order is ID, Name, Email, Theme.
    $before = $component->html();
    $namePos = strpos($before, 'Name Column');
    $themePos = strpos($before, 'Theme Column');
    expect($namePos)->toBeLessThan($themePos);

    // Move 'theme' to the front of the toggleable columns.
    $component->call('sortColumns', 'theme', 0);

    $after = $component->html();
    $themePosAfter = strpos($after, 'Theme Column');
    $namePosAfter = strpos($after, 'Name Column');

    expect($themePosAfter)->toBeLessThan($namePosAfter);
});

test('the fixed column always renders first regardless of toggleable reordering', function () {
    User::factory()->create();

    $component = Livewire::test(OrderRenderTestTable::class)
        ->call('sortColumns', 'theme', 0)
        ->call('sortColumns', 'email', 0);

    $html = $component->html();
    $idPos = strpos($html, 'ID Column');
    $emailPos = strpos($html, 'Email Column');

    expect($idPos)->toBeLessThan($emailPos);
});

test('saved column order is restored as the actual rendered order after remount', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(OrderRenderTestTable::class)
        ->call('sortColumns', 'theme', 0);

    // Fresh component instance simulating a remount/new page load.
    $html = Livewire::actingAs($user)->test(OrderRenderTestTable::class)->html();

    $themePos = strpos($html, 'Theme Column');
    $namePos = strpos($html, 'Name Column');

    expect($themePos)->toBeLessThan($namePos);
});

test('the column manager itself lists toggleable columns in the persisted order', function () {
    User::factory()->create();

    $component = Livewire::test(OrderRenderTestTable::class)
        ->call('sortColumns', 'theme', 0);

    $html = $component->html();

    // The column-manager dropdown item order should also reflect the reorder,
    // not just the underlying table body.
    $themeTogglePos = strpos($html, "toggleColumn('theme')");
    $nameTogglePos = strpos($html, "toggleColumn('name')");

    expect($themeTogglePos)->toBeLessThan($nameTogglePos);
});

test('an unauthorized column can never be injected into the rendered order via columnOrder', function () {
    User::factory()->create();

    $component = Livewire::test(new class extends Table
    {
        protected string $tableKey = 'order-render-auth-test';

        protected ?string $model = User::class;

        protected function columns(): array
        {
            return [
                TextColumn::make('name'),
                TextColumn::make('secret')->visible(false)->label('TOTALLY-SECRET-ORDER-LABEL'),
            ];
        }

        protected function filters(): array
        {
            return [];
        }
    }::class);

    $component->set('columnOrder', ['secret', 'name']);

    expect($component->html())->not->toContain('TOTALLY-SECRET-ORDER-LABEL');
});
