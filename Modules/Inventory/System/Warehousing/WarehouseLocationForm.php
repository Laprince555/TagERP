<?php

namespace Modules\Inventory\System\Warehousing;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\RelationListField;
use App\Support\DynamicForm\Core\Fields\SelectField;
use App\Support\DynamicForm\Core\Fields\TextField;
use Modules\General\Models\Uom;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Models\WarehouseLocation;

/**
 * Create/edit-form definition for WarehouseLocation, the SubApplication
 * (inv-whs-wrh-loc) embedded in Warehouse's record view. Code is generated
 * by WarehouseLocation::booted() from the aisle/shelf/bin path, so it is not
 * a field here.
 *
 * NOTE: Modules\General\Models\Uom does not exist yet (out of scope for this
 * slice — models are frozen). The uom_id field below will error until that
 * model is created; flagged in the build report.
 */
class WarehouseLocationForm extends DynamicForm
{
    public function model(): string
    {
        return WarehouseLocation::class;
    }

    public function fields(): array
    {
        return [
            RelationListField::make('warehouse')
                ->model(Warehouse::class)
                ->field('name')
                ->column('warehouse_id')
                ->label('Warehouse')
                ->required(),
            SelectField::make('level')
                ->label('Level')
                ->options(['1' => 'Aisle', '2' => 'Shelf', '3' => 'Bin'])
                ->required()
                ->rules(['integer', 'in:1,2,3']),
            TextField::make('aisle')->label('Aisle')->rules(['max:255']),
            TextField::make('shelf')->label('Shelf')->rules(['max:255']),
            TextField::make('bin')->label('Bin')->rules(['max:255']),
            TextField::make('capacity_units')
                ->type('number')
                ->label('Capacity (units)')
                ->required()
                ->rules(['numeric', 'min:0']),
            RelationListField::make('uom')
                ->model(Uom::class)
                ->field('code')
                ->column('uom_id')
                ->label('Unit of Measure')
                ->required(),
        ];
    }
}
