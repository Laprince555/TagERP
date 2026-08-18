<?php

namespace Modules\Inventory\Providers;

use App\Support\DynamicForm\Core\FormDefinitionRegistry;
use App\Support\DynamicRecordView\Core\RecordViewRegistry;
use App\Support\RecordReference\RecordReferenceRegistry;
use Livewire\Livewire;
use Modules\Inventory\Livewire\StockMovements\AllocationRecordView;
use Modules\Inventory\Livewire\StockMovements\AllocationsIndex;
use Modules\Inventory\Livewire\StockMovements\AllocationsTable;
use Modules\Inventory\Livewire\StockMovements\BatchLotRecordView;
use Modules\Inventory\Livewire\StockMovements\BatchLotsIndex;
use Modules\Inventory\Livewire\StockMovements\BatchLotsTable;
use Modules\Inventory\Livewire\StockMovements\InventoryMovementRecordView;
use Modules\Inventory\Livewire\StockMovements\InventoryMovementsIndex;
use Modules\Inventory\Livewire\StockMovements\InventoryMovementsTable;
use Modules\Inventory\Livewire\Warehousing\CycleCountEditor;
use Modules\Inventory\Livewire\Warehousing\CycleCountLinesTable;
use Modules\Inventory\Livewire\Warehousing\CycleCountRecordView;
use Modules\Inventory\Livewire\Warehousing\CycleCountsIndex;
use Modules\Inventory\Livewire\Warehousing\CycleCountsTable;
use Modules\Inventory\Livewire\Warehousing\WarehouseLocationsTable;
use Modules\Inventory\Livewire\Warehousing\WarehouseRecordView;
use Modules\Inventory\Livewire\Warehousing\WarehousesIndex;
use Modules\Inventory\Livewire\Warehousing\WarehousesTable;
use Modules\Inventory\System\StockMovements\AllocationForm;
use Modules\Inventory\System\StockMovements\AllocationRecordReferenceProvider;
use Modules\Inventory\System\StockMovements\AllocationRecordView as AllocationRecordViewDefinition;
use Modules\Inventory\System\StockMovements\BatchLotForm;
use Modules\Inventory\System\StockMovements\BatchLotRecordReferenceProvider;
use Modules\Inventory\System\StockMovements\BatchLotRecordView as BatchLotRecordViewDefinition;
use Modules\Inventory\System\StockMovements\InventoryMovementRecordReferenceProvider;
use Modules\Inventory\System\StockMovements\InventoryMovementRecordView as InventoryMovementRecordViewDefinition;
use Modules\Inventory\System\Warehousing\CycleCountForm;
use Modules\Inventory\System\Warehousing\CycleCountRecordReferenceProvider;
use Modules\Inventory\System\Warehousing\CycleCountRecordView as CycleCountRecordViewDefinition;
use Modules\Inventory\System\Warehousing\WarehouseForm;
use Modules\Inventory\System\Warehousing\WarehouseLocationForm;
use Modules\Inventory\System\Warehousing\WarehouseLocationRecordReferenceProvider;
use Modules\Inventory\System\Warehousing\WarehouseRecordReferenceProvider;
use Modules\Inventory\System\Warehousing\WarehouseRecordView as WarehouseRecordViewDefinition;
use Nwidart\Modules\Support\ModuleServiceProvider;

class InventoryServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Inventory';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'inventory';

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        // Register Livewire components
        Livewire::component('inventory.warehousing.warehouses-index', WarehousesIndex::class);
        Livewire::component('inventory.warehousing.warehouses-table', WarehousesTable::class);
        Livewire::component('inventory.warehousing.warehouse-record-view', WarehouseRecordView::class);
        Livewire::component('inventory.warehousing.warehouse-locations-table', WarehouseLocationsTable::class);

        Livewire::component('inventory.warehousing.cycle-counts-index', CycleCountsIndex::class);
        Livewire::component('inventory.warehousing.cycle-counts-table', CycleCountsTable::class);
        Livewire::component('inventory.warehousing.cycle-count-record-view', CycleCountRecordView::class);
        Livewire::component('inventory.warehousing.cycle-count-lines-table', CycleCountLinesTable::class);
        Livewire::component('inventory.warehousing.cycle-count-editor', CycleCountEditor::class);

        Livewire::component('inventory.stock-movements.movements-index', InventoryMovementsIndex::class);
        Livewire::component('inventory.stock-movements.movements-table', InventoryMovementsTable::class);
        Livewire::component('inventory.stock-movements.movement-record-view', InventoryMovementRecordView::class);

        Livewire::component('inventory.stock-movements.batch-lots-index', BatchLotsIndex::class);
        Livewire::component('inventory.stock-movements.batch-lots-table', BatchLotsTable::class);
        Livewire::component('inventory.stock-movements.batch-lot-record-view', BatchLotRecordView::class);

        Livewire::component('inventory.stock-movements.allocations-index', AllocationsIndex::class);
        Livewire::component('inventory.stock-movements.allocations-table', AllocationsTable::class);
        Livewire::component('inventory.stock-movements.allocation-record-view', AllocationRecordView::class);

        // Register RecordReferences
        $recordReferenceRegistry = $this->app->make(RecordReferenceRegistry::class);
        $recordReferenceRegistry->register(new WarehouseRecordReferenceProvider);
        $recordReferenceRegistry->register(new WarehouseLocationRecordReferenceProvider);
        $recordReferenceRegistry->register(new CycleCountRecordReferenceProvider);
        $recordReferenceRegistry->register(new InventoryMovementRecordReferenceProvider);
        $recordReferenceRegistry->register(new BatchLotRecordReferenceProvider);
        $recordReferenceRegistry->register(new AllocationRecordReferenceProvider);

        // Register RecordViews
        $recordViewRegistry = $this->app->make(RecordViewRegistry::class);
        $recordViewRegistry->register('inventory.warehousing.warehouse', WarehouseRecordViewDefinition::class);
        $recordViewRegistry->register('inventory.warehousing.cycle-count', CycleCountRecordViewDefinition::class);
        $recordViewRegistry->register('inventory.stock-movements.movement', InventoryMovementRecordViewDefinition::class);
        $recordViewRegistry->register('inventory.stock-movements.batch-lot', BatchLotRecordViewDefinition::class);
        $recordViewRegistry->register('inventory.stock-movements.allocation', AllocationRecordViewDefinition::class);

        // Register Forms
        $formRegistry = $this->app->make(FormDefinitionRegistry::class);
        $formRegistry->register('inventory.warehousing.warehouse.create', WarehouseForm::class);
        $formRegistry->register('inventory.warehousing.warehouse-location.create', WarehouseLocationForm::class);
        $formRegistry->register('inventory.warehousing.cycle-count.create', CycleCountForm::class);
        $formRegistry->register('inventory.stock-movements.batch-lot.create', BatchLotForm::class);
        $formRegistry->register('inventory.stock-movements.allocation.create', AllocationForm::class);
    }
}
