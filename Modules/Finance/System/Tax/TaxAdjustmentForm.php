<?php

namespace Modules\Finance\System\Tax;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\DateField;
use App\Support\DynamicForm\Core\Fields\RelationListField;
use App\Support\DynamicForm\Core\Fields\SelectField;
use App\Support\DynamicForm\Core\Fields\TextareaField;
use App\Support\DynamicForm\Core\Fields\TextField;
use Modules\Finance\Models\Tax\Tax;
use Modules\Finance\Models\Tax\TaxAdjustment;

/**
 * Create-form definition for the "fin-tax-adj" Application.
 */
class TaxAdjustmentForm extends DynamicForm
{
    public function model(): string
    {
        return TaxAdjustment::class;
    }

    public function fields(): array
    {
        return [
            RelationListField::make('tax')
                ->model(Tax::class)
                ->createForm('finance.tax.tax.create')
                ->field('name')
                ->column('tax_id')
                ->label('Tax')
                ->required(),
            DateField::make('adjustment_date')->label('Adjustment Date')->required(),
            SelectField::make('reason')
                ->label('Reason')
                ->options(['settlement' => 'Settlement', 'inspection_result' => 'Inspection Result'])
                ->required(),
            TextField::make('amount')->type('number')->label('Amount')->required()->rules(['numeric', 'gt:0']),
            TextareaField::make('description')->label('Description'),
        ];
    }
}
