<?php

namespace Modules\Finance\System\AccountsPayable;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\RelationListField;
use App\Support\DynamicForm\Core\Fields\SelectField;
use App\Support\DynamicForm\Core\Fields\TextField;
use Modules\Finance\Models\AccountsPayable\Deduction;
use Modules\Finance\Models\AccountsPayable\DeductionCategory;

/**
 * Create-form definition for the "fin-ap-ddc" Application.
 */
class DeductionForm extends DynamicForm
{
    public function model(): string
    {
        return Deduction::class;
    }

    public function fields(): array
    {
        return [
            TextField::make('name')->label('Name')->required(),
            RelationListField::make('category')
                ->model(DeductionCategory::class)
                ->createForm('finance.accounts-payable.deduction-category.create')
                ->field('name')
                ->column('deduction_category_id')
                ->label('Deduction Category')
                ->required(),
            SelectField::make('calculation_type')
                ->label('Calculation Type')
                ->options(['fixed' => 'Fixed Amount', 'percentage' => 'Percentage'])
                ->required(),
            TextField::make('value')->type('number')->label('Value')->required()->rules(['numeric', 'min:0']),
        ];
    }
}
