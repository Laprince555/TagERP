<?php

namespace Modules\General\Livewire\World\People;

use App\Livewire\DynamicTable\Table;
use App\Support\DynamicTable\Core\Columns\BooleanColumn;
use App\Support\DynamicTable\Core\Columns\DateColumn;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\General\Models\World\People\PersonPosition;

/**
 * Embedded-only: a Person's job/role history ("Positions" Other Data tab).
 * Never a standalone Application route, so no access gate of its own — the
 * parent Person's query() already gates the page this is embedded on.
 */
class PersonPositionsTable extends Table
{
    protected string $tableKey = 'general-world-person-positions';

    protected ?string $model = PersonPosition::class;

    protected function query(): Builder
    {
        return PersonPosition::query()->with('company');
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('position')->sortable()->searchable()->label('Position'),
            RecordReferenceColumn::make('company')
                ->applicationCode('gen-wld-com')
                ->relation('company')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Company'),
            DateColumn::make('start_date')->sortable()->label('Start Date'),
            DateColumn::make('end_date')->sortable()->label('End Date'),
            BooleanColumn::make('is_current')->sortable()->label('Current'),
        ];
    }

    protected function filters(): array
    {
        return [
            TextFilter::make('position'),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('start_date')->descending()];
    }
}
