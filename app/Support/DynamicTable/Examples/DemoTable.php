<?php

namespace App\Support\DynamicTable\Examples;

use App\Models\User;
use App\Support\DynamicTable\Core\Column;
use App\Support\DynamicTable\Core\Columns\BooleanColumn;
use App\Support\DynamicTable\Core\Columns\ComputedColumn;
use App\Support\DynamicTable\Core\Columns\DateTimeColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filter;
use App\Support\DynamicTable\Core\Filters\DateFilter;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use Illuminate\Database\Eloquent\Builder;

/**
 * Canonical demo/reference table for the Dynamic Table Engine, built on the
 * existing App\Models\User model. Exercises most column/filter/sort features
 * and is exercised by the Milestone 3 Livewire feature tests.
 */
class DemoTable
{
    public static function tableKey(): string
    {
        return 'demo-users';
    }

    /**
     * @return array<int, Column>
     */
    public static function columns(): array
    {
        return [
            TextColumn::make('name')->sortable()->searchable()->label('Name'),
            TextColumn::make('email')->sortable()->searchable()->label('Email'),
            ComputedColumn::make('email_domain')->field('email')
                ->formatUsing(fn ($value) => str($value)->after('@')->toString())
                ->label('Domain'),
            BooleanColumn::make('is_verified')->field('email_verified_at')
                ->formatUsing(fn ($value) => $value !== null)
                ->label('Verified'),
            DateTimeColumn::make('created_at')->sortable()->label('Joined'),
            TextColumn::make('theme')->hiddenByDefault()->toggleable()->label('Theme'),
        ];
    }

    /**
     * @return array<int, Filter>
     */
    public static function filters(): array
    {
        return [
            TextFilter::make('name'),
            TextFilter::make('email'),
            DateFilter::make('created_at'),
        ];
    }

    public static function query(): Builder
    {
        return User::query();
    }

    /**
     * @return Sort[]
     */
    public static function defaultSort(): array
    {
        return [Sort::make('name')->ascending()];
    }
}
