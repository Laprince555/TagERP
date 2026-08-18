<?php

namespace Modules\Inventory\System\Warehousing;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\DateField;
use App\Support\DynamicForm\Core\Fields\RelationListField;
use App\Support\DynamicForm\Core\Fields\TextareaField;
use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Models\CycleCount;
use Modules\Inventory\Models\Warehouse;

/**
 * Create/edit-form definition for the "inv-whs-ccn" Application — the count
 * header only (warehouse, date, notes). Lines are counted afterward on the
 * batch entry screen (CycleCountEditor); nothing here touches status, which
 * only ever moves through the record view's start/complete/apply-variance
 * actions.
 */
class CycleCountForm extends DynamicForm
{
    public function model(): string
    {
        return CycleCount::class;
    }

    /**
     * Only a still-scheduled count is a plain header edit — once counting has
     * started the header describes work already in progress.
     */
    public function authorizeUpdate(Model $record): bool
    {
        /** @var CycleCount $record */
        return $record->status === 'scheduled';
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
            DateField::make('count_date')->label('Count Date')->required(),
            TextareaField::make('notes')->label('Notes'),
        ];
    }
}
