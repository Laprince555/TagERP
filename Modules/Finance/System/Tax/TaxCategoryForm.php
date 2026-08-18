<?php

namespace Modules\Finance\System\Tax;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\RelationListField;
use App\Support\DynamicForm\Core\Fields\TextField;
use Modules\Finance\Models\Tax\TaxCategory;
use Modules\General\Models\World\Country;

/**
 * Create-form definition for the "fin-tax-cat" Application.
 */
class TaxCategoryForm extends DynamicForm
{
    public function model(): string
    {
        return TaxCategory::class;
    }

    public function fields(): array
    {
        return [
            TextField::make('name')->label('Name')->required(),
            RelationListField::make('country')
                ->model(Country::class)
                ->field('name')
                ->column('country_id')
                ->label('Country')
                ->required(),
            TextField::make('description')->label('Description'),
        ];
    }
}
