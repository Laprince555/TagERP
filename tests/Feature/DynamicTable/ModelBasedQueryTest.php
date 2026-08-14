<?php

use App\Livewire\DynamicTable\Table;
use App\Models\User;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Exceptions\InvalidModelException;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Livewire;

test('a table with a model property gets a safe default query without overriding query()', function () {
    User::factory()->create(['name' => 'Model Default User']);

    $component = new class extends Table
    {
        protected string $tableKey = 'model-default';

        protected ?string $model = User::class;

        protected function columns(): array
        {
            return [TextColumn::make('name')->searchable()];
        }

        protected function filters(): array
        {
            return [];
        }
    };

    Livewire::test($component::class)->assertSee('Model Default User');
});

test('a table can still override query() to scope the models default query', function () {
    User::factory()->create(['name' => 'Included User']);
    User::factory()->create(['name' => 'Excluded User']);

    $component = new class extends Table
    {
        protected string $tableKey = 'model-override';

        protected ?string $model = User::class;

        protected function columns(): array
        {
            return [TextColumn::make('name')->searchable()];
        }

        protected function filters(): array
        {
            return [];
        }

        protected function query(): Builder
        {
            return parent::query()->where('name', 'Included User');
        }
    };

    Livewire::test($component::class)
        ->assertSee('Included User')
        ->assertDontSee('Excluded User');
});

test('a table with neither a model nor an overridden query throws InvalidModelException', function () {
    $component = new class extends Table
    {
        protected string $tableKey = 'model-missing';

        protected function columns(): array
        {
            return [];
        }

        protected function filters(): array
        {
            return [];
        }
    };

    try {
        Livewire::test($component::class);
        $this->fail('Expected an exception to be thrown.');
    } catch (Throwable $e) {
        $root = $e;
        while ($root->getPrevious() !== null) {
            $root = $root->getPrevious();
        }
        expect($root)->toBeInstanceOf(InvalidModelException::class);
    }
});

test('a table with a non model class string throws InvalidModelException', function () {
    $component = new class extends Table
    {
        protected string $tableKey = 'model-invalid';

        protected ?string $model = stdClass::class;

        protected function columns(): array
        {
            return [];
        }

        protected function filters(): array
        {
            return [];
        }
    };

    try {
        Livewire::test($component::class);
        $this->fail('Expected an exception to be thrown.');
    } catch (Throwable $e) {
        $root = $e;
        while ($root->getPrevious() !== null) {
            $root = $root->getPrevious();
        }
        expect($root)->toBeInstanceOf(InvalidModelException::class);
    }
});
