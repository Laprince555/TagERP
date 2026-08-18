<?php

namespace Modules\Finance\System\AccountsPayable;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\TextField;
use Modules\Finance\Models\AccountsPayable\DeductionCategory;

/**
 * Create-form definition for the "fin-ap-dcc" Application.
 */
class DeductionCategoryForm extends DynamicForm
{
    public function model(): string
    {
        return DeductionCategory::class;
    }

    public function fields(): array
    {
        return [
            TextField::make('name')->label('Name')->required(),
            TextField::make('description')->label('Description'),
        ];
    }
}
