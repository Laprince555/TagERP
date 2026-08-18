<?php

use App\Support\ModuleRoute;
use Illuminate\Support\Facades\Route;
use Modules\General\Livewire\ModuleWorkspace;
use Modules\General\Livewire\SubModuleWorkspace;
use Modules\Inventory\Livewire\StockMovements\AllocationRecordView;
use Modules\Inventory\Livewire\StockMovements\AllocationsIndex;
use Modules\Inventory\Livewire\StockMovements\BatchLotRecordView;
use Modules\Inventory\Livewire\StockMovements\BatchLotsIndex;
use Modules\Inventory\Livewire\StockMovements\InventoryMovementRecordView;
use Modules\Inventory\Livewire\StockMovements\InventoryMovementsIndex;
use Modules\Inventory\Livewire\Warehousing\CycleCountEditor;
use Modules\Inventory\Livewire\Warehousing\CycleCountRecordView;
use Modules\Inventory\Livewire\Warehousing\CycleCountsIndex;
use Modules\Inventory\Livewire\Warehousing\WarehouseRecordView;
use Modules\Inventory\Livewire\Warehousing\WarehousesIndex;

ModuleRoute::registerIndex('inventory', '/inventory', ModuleWorkspace::class);
ModuleRoute::registerSubModules('inventory', '/inventory', SubModuleWorkspace::class);

Route::middleware(['auth'])->group(function (): void {
    Route::get('/inventory/warehousing/warehouses', WarehousesIndex::class)
        ->name('inventory.warehousing.warehouses');
    Route::get('/inventory/warehousing/warehouses/{recordId}/view', WarehouseRecordView::class)
        ->name('inventory.warehousing.warehouses.show');

    Route::get('/inventory/warehousing/cycle-counts', CycleCountsIndex::class)
        ->name('inventory.warehousing.cycle-counts');
    Route::get('/inventory/warehousing/cycle-counts/{recordId}/view', CycleCountRecordView::class)
        ->name('inventory.warehousing.cycle-counts.show');
    Route::get('/inventory/warehousing/cycle-counts/{recordId}/edit', CycleCountEditor::class)
        ->name('inventory.warehousing.cycle-counts.edit');

    Route::get('/inventory/stock-movements/movements', InventoryMovementsIndex::class)
        ->name('inventory.stock-movements.movements');
    Route::get('/inventory/stock-movements/movements/{recordId}/view', InventoryMovementRecordView::class)
        ->name('inventory.stock-movements.movements.show');

    Route::get('/inventory/stock-movements/batch-lots', BatchLotsIndex::class)
        ->name('inventory.stock-movements.batch-lots');
    Route::get('/inventory/stock-movements/batch-lots/{recordId}/view', BatchLotRecordView::class)
        ->name('inventory.stock-movements.batch-lots.show');

    Route::get('/inventory/stock-movements/allocations', AllocationsIndex::class)
        ->name('inventory.stock-movements.allocations');
    Route::get('/inventory/stock-movements/allocations/{recordId}/view', AllocationRecordView::class)
        ->name('inventory.stock-movements.allocations.show');
});
