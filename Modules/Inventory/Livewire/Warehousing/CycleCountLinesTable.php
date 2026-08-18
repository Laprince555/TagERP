<?php

namespace Modules\Inventory\Livewire\Warehousing;

use App\Livewire\DynamicTable\Table;
use App\Support\DynamicTable\Core\Columns\ComputedColumn;
use App\Support\DynamicTable\Core\Columns\NumberColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Sort;
use Illuminate\Database\Eloquent\Builder;
use Modules\Inventory\Models\CycleCountLine;

/**
 * Embedded-only: the lines of one cycle count — the "Lines" tab on a
 * CycleCount's record view. Never a standalone route, so the parent's
 * query() is the access gate.
 *
 * ponytail: `item_id` is shown as a raw id, not through a RecordReferenceColumn
 * — the migration itself notes "no Item Eloquent model exists yet" and
 * CycleCountLine::item() points at a class that doesn't exist. Swap in
 * RecordReferenceColumn::make('item')->applicationCode(Item::APPLICATION_CODE)
 * once that model lands. `location` has no APPLICATION_CODE (WarehouseLocation
 * carries level/aisle/shelf/bin, not a display name), so it's a computed
 * column rather than a RecordReferenceColumn, per the relation-display rule.
 */
class CycleCountLinesTable extends Table
{
    protected string $tableKey = 'inventory-warehousing-cycle-count-lines';

    protected ?string $model = CycleCountLine::class;

    protected function query(): Builder
    {
        return CycleCountLine::query()->with('location');
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('code')->sortable()->searchable()->label('#'),
            NumberColumn::make('item_id')->label('Item ID'),
            ComputedColumn::make('location')
                ->label('Location')
                ->formatUsing(fn (mixed $value, mixed $row): string => $row?->location
                    ? collect([$row->location->aisle, $row->location->shelf, $row->location->bin])->filter()->implode('-')
                    : '—'),
            NumberColumn::make('system_qty')->label('System Qty'),
            NumberColumn::make('physical_qty')->label('Physical Qty')->placeholder('—'),
            ComputedColumn::make('variance')
                ->label('Variance')
                ->formatUsing(fn (mixed $value, mixed $row): string => $row?->physical_qty === null ? '—' : (string) $row->variance),
        ];
    }

    protected function filters(): array
    {
        return [];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('code')->ascending()];
    }
}
