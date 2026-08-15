<?php

use App\Livewire\DynamicTable\Table;
use App\Models\User;
use App\Support\DynamicTable\Core\Columns\NumberColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use Livewire\Livewire;

class SummaryTestTable extends Table
{
    protected string $tableKey = 'summary-test';

    protected ?string $model = User::class;

    protected function columns(): array
    {
        return [
            TextColumn::make('name')->searchable(),
            NumberColumn::make('id')->summary('count'),
        ];
    }

    protected function filters(): array
    {
        return [];
    }
}

class NoSummaryTestTable extends Table
{
    protected string $tableKey = 'no-summary-test';

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

function callSummaries(Table $component): array
{
    return (new ReflectionMethod($component, 'summaries'))->invoke($component);
}

test('summary aggregates only the currently filtered rows', function () {
    User::factory()->count(5)->create();

    $test = Livewire::test(SummaryTestTable::class);
    expect(callSummaries($test->instance()))->toBe(['id' => 5]);

    $target = User::first();
    $test->set('search', $target->name);
    expect(callSummaries($test->instance()))->toBe(['id' => 1]);
});

test('summary returns empty array when no column declares one', function () {
    User::factory()->count(2)->create();

    $test = Livewire::test(NoSummaryTestTable::class);
    expect(callSummaries($test->instance()))->toBe([]);
});
