<?php

namespace Modules\Finance\System\Tax;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\RelationListField;
use App\Support\DynamicForm\Core\Fields\SelectField;
use App\Support\DynamicForm\Core\Fields\TextField;
use Modules\Finance\Models\Tax\Tax;
use Modules\Finance\Models\Tax\TaxCategory;

/**
 * Create-form definition for the "fin-tax-tax" Application.
 */
class TaxForm extends DynamicForm
{
    public function model(): string
    {
        return Tax::class;
    }

    public function fields(): array
    {
        return [
            TextField::make('name')->label('Name')->required(),
            RelationListField::make('category')
                ->model(TaxCategory::class)
                ->createForm('finance.tax.tax-category.create')
                ->field('name')
                ->column('tax_category_id')
                ->label('Tax Category')
                ->required(),
            SelectField::make('direction')
                ->label('Direction')
                ->options(['addition' => 'Addition', 'deduction' => 'Withholding'])
                ->required(),
            TextField::make('rate')->type('number')->label('Rate (%)')->required()->rules(['numeric', 'min:0']),
        ];
    }
}
