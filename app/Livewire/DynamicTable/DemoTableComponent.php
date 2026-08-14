<?php

namespace App\Livewire\DynamicTable;

use App\Support\DynamicTable\Examples\DemoTable;
use Illuminate\Database\Eloquent\Builder;

class DemoTableComponent extends Table
{
    protected string $tableKey = 'demo-users';

    protected function columns(): array
    {
        return DemoTable::columns();
    }

    protected function filters(): array
    {
        return DemoTable::filters();
    }

    protected function query(): Builder
    {
        return DemoTable::query();
    }

    protected function defaultSort(): array
    {
        return DemoTable::defaultSort();
    }
}
