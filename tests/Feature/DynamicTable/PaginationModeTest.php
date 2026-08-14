<?php

use App\Livewire\DynamicTable\Table;
use App\Models\User;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

class StandardPaginationTable extends Table
{
    protected string $tableKey = 'pagination-standard-test';

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

class SimplePaginationTable extends Table
{
    protected string $tableKey = 'pagination-simple-test';

    protected ?string $model = User::class;

    protected function columns(): array
    {
        return [TextColumn::make('name')];
    }

    protected function filters(): array
    {
        return [];
    }

    protected function paginationMode(): string
    {
        return 'simple';
    }
}

class CursorPaginationTable extends Table
{
    protected string $tableKey = 'pagination-cursor-test';

    protected ?string $model = User::class;

    protected function columns(): array
    {
        return [TextColumn::make('name')];
    }

    protected function filters(): array
    {
        return [];
    }

    protected function paginationMode(): string
    {
        return 'cursor';
    }
}

class InvalidPaginationTable extends Table
{
    protected string $tableKey = 'pagination-invalid-test';

    protected ?string $model = User::class;

    protected function columns(): array
    {
        return [TextColumn::make('name')];
    }

    protected function filters(): array
    {
        return [];
    }

    protected function paginationMode(): string
    {
        return 'invalid';
    }
}

test('cursor pagination mode issues no count query and renders', function () {
    User::factory()->count(5)->create();

    DB::enableQueryLog();
    $html = Livewire::test(CursorPaginationTable::class)->html();
    $hasCountQuery = collect(DB::getQueryLog())->pluck('query')->contains(fn ($q) => str_contains($q, 'count('));
    DB::disableQueryLog();

    expect($hasCountQuery)->toBeFalse()
        ->and($html)->toContain('Cursor Pagination');
});

test('standard pagination mode shows total count and issues a count query', function () {
    User::factory()->count(5)->create();

    DB::enableQueryLog();
    $html = Livewire::test(StandardPaginationTable::class)->html();
    $hasCountQuery = collect(DB::getQueryLog())->pluck('query')->contains(fn ($q) => str_contains($q, 'count('));
    DB::disableQueryLog();

    expect($hasCountQuery)->toBeTrue()
        ->and($html)->toContain('of 5');
});

test('simple pagination mode never calls total and issues no count query', function () {
    User::factory()->count(5)->create();

    DB::enableQueryLog();
    $html = Livewire::test(SimplePaginationTable::class)->html();
    $hasCountQuery = collect(DB::getQueryLog())->pluck('query')->contains(fn ($q) => str_contains($q, 'count('));
    DB::disableQueryLog();

    expect($hasCountQuery)->toBeFalse()
        ->and($html)->not->toContain('of 5')
        ->and($html)->toContain('Page 1');
});

test('simple pagination next and previous still work without a total', function () {
    User::factory()->count(30)->create();

    Livewire::test(SimplePaginationTable::class)
        ->call('gotoPage', 2)
        ->assertOk()
        ->assertSet('page', 2);
});

test('requesting an unsupported pagination mode throws instead of silently falling back', function () {
    try {
        Livewire::test(InvalidPaginationTable::class);
        test()->fail('Expected an exception to be thrown.');
    } catch (Throwable $e) {
        $root = $e;
        while ($root->getPrevious() !== null) {
            $root = $root->getPrevious();
        }
        expect($root)->toBeInstanceOf(InvalidArgumentException::class);
    }
});
