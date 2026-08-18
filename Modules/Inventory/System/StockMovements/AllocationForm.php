<?php

namespace Modules\Inventory\System\StockMovements;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\RelationListField;
use App\Support\DynamicForm\Core\Fields\SelectField;
use App\Support\DynamicForm\Core\Fields\TextField;
use Modules\Inventory\Models\Allocation;
use Modules\Inventory\Models\WarehouseLocation;

/**
 * Create-form definition for the "inv-stk-alc" Application.
 */
class AllocationForm extends DynamicForm
{
    public function model(): string
    {
        return Allocation::class;
    }

    public function fields(): array
    {
        return [
            TextField::make('item_id')->label('Item')->required()->rules(['integer', 'exists:items,id']),
            RelationListField::make('location')
                ->model(WarehouseLocation::class)
                ->field('bin')
                ->column('location_id')
                ->label('Warehouse Location')
                ->required(),
            TextField::make('allocated_qty')->label('Allocated Quantity')->required()->rules(['numeric', 'min:0']),
            TextField::make('reference_order_type')->label('Reference Order Type')->required()->rules(['max:255']),
            TextField::make('reference_order_id')->label('Reference Order Id')->required()->rules(['max:255']),
            SelectField::make('status')
                ->label('Status')
                ->options(['pending' => 'Pending', 'fulfilled' => 'Fulfilled', 'cancelled' => 'Cancelled'])
                ->required()
                ->default('pending'),
        ];
    }
}
