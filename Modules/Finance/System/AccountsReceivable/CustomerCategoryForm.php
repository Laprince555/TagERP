<?php

namespace Modules\Finance\System\AccountsReceivable;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\TextField;
use Modules\Finance\Models\AccountsReceivable\CustomerCategory;

/**
 * Create-form definition for the "fin-ar-cct" Application.
 */
class CustomerCategoryForm extends DynamicForm
{
    public function model(): string
    {
        return CustomerCategory::class;
    }

    public function fields(): array
    {
        return [
            TextField::make('name')->label('Name')->required(),
            TextField::make('description')->label('Description'),
        ];
    }
}
