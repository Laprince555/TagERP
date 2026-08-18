<?php

namespace Modules\Inventory\Livewire\Warehousing;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Livewire\DynamicTable\Table;
use App\Support\DynamicTable\Core\Columns\DateColumn;
use App\Support\DynamicTable\Core\Columns\EnumColumn;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Filters\DateFilter;
use App\Support\DynamicTable\Core\Filters\EnumFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\Inventory\Models\CycleCount;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\System\Warehousing\CycleCountStatus;

/**
 * Cycle counts index for the "inv-whs-ccn" Application
 * (inventory.warehousing.cycle-counts).
 */
class CycleCountsTable extends Table
{
    protected string $tableKey = 'inventory-warehousing-cycle-counts';

    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return CycleCount::APPLICATION_CODE;
    }

    protected function query(): Builder
    {
        return CycleCount::query()->with('warehouse');
    }

    protected function columns(): array
    {
        return [
            RecordReferenceColumn::make('code')
                ->applicationCode(CycleCount::APPLICATION_CODE)
                ->variant(RecordReferenceVariant::Tag)
                ->sortable()
                ->searchable()
                ->label('Code'),
            RecordReferenceColumn::make('warehouse')
                ->applicationCode(Warehouse::APPLICATION_CODE)
                ->relation('warehouse')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Warehouse'),
            DateColumn::make('count_date')->sortable()->label('Count Date'),
            EnumColumn::make('status')
                ->enum(CycleCountStatus::class)
                ->sortable()
                ->label('Status')
                ->formatUsing(fn (mixed $value): string => CycleCountStatus::tryFrom((string) $value)?->label() ?? (string) $value),
        ];
    }

    protected function filters(): array
    {
        return [
            DateFilter::make('count_date'),
            EnumFilter::make('status')->enum(CycleCountStatus::class),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('count_date')->descending()];
    }

    protected function createForm(): ?string
    {
        return 'inventory.warehousing.cycle-count.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Cycle Count');
    }
}
