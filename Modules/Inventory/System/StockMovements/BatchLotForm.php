<?php

namespace Modules\Inventory\System\StockMovements;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\DateField;
use App\Support\DynamicForm\Core\Fields\RelationListField;
use App\Support\DynamicForm\Core\Fields\TextField;
use Modules\Inventory\Models\BatchLot;
use Modules\Inventory\Models\Warehouse;

/**
 * Create-form definition for the "inv-stk-bat" Application.
 */
class BatchLotForm extends DynamicForm
{
    public function model(): string
    {
        return BatchLot::class;
    }

    public function fields(): array
    {
        return [
            TextField::make('batch_number')->label('Batch Number')->required()->rules(['max:255']),
            TextField::make('item_id')->label('Item')->required()->rules(['integer', 'exists:items,id']),
            RelationListField::make('warehouse')
                ->model(Warehouse::class)
                ->createForm('inventory.warehouse.create')
                ->field('name')
                ->column('warehouse_id')
                ->label('Warehouse')
                ->required(),
            DateField::make('expiry_date')->label('Expiry Date'),
            TextField::make('cost_per_unit')->label('Cost Per Unit')->required()->rules(['numeric', 'min:0']),
            TextField::make('qty_available')->label('Quantity Available')->required()->rules(['numeric', 'min:0']),
        ];
    }
}
