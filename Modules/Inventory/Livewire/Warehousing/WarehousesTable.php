<?php

namespace Modules\Inventory\Livewire\Warehousing;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Livewire\DynamicTable\Table;
use App\Services\NavigationTreeService;
use App\Support\DynamicTable\Core\Columns\NumberColumn;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceAccess;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\HR\Models\OrganizationStructure\Branch;
use Modules\Inventory\Models\Warehouse;

/**
 * Warehouses index for the "inv-whs-wrh" Application
 * (inventory.warehousing.warehouses).
 */
class WarehousesTable extends Table
{
    protected string $tableKey = 'inventory-warehousing-warehouses';

    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return Warehouse::APPLICATION_CODE;
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Warehouse::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Warehouse::query()->whereKey(-1);
        }

        return Warehouse::query()->with('branch');
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('code')->sortable()->searchable()->label('Code'),
            TextColumn::make('name')->sortable()->searchable()->label('Name'),
            NumberColumn::make('capacity_m3')->sortable()->label('Capacity (m³)'),
            RecordReferenceColumn::make('branch')
                ->applicationCode(Branch::APPLICATION_CODE)
                ->relation('branch')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Branch'),
        ];
    }

    protected function filters(): array
    {
        return [
            TextFilter::make('name'),
            TextFilter::make('code'),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('name')->ascending()];
    }

    protected function createForm(): ?string
    {
        return 'inventory.warehousing.warehouse.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Warehouse');
    }
}
