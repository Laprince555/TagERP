<?php

namespace Modules\Finance\System\CashAndBanks;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\SelectField;
use App\Support\DynamicForm\Core\Fields\TextField;
use Modules\Finance\Models\CashAndBanks\Categories\BankCategory;

class BankCategoryForm extends DynamicForm
{
    public function model(): string
    {
        return BankCategory::class;
    }

    public function fields(): array
    {
        return [
            TextField::make('name')->label('Name')->required(),

            SelectField::make('parent_id')
                ->label('Parent Category')
                ->options(BankCategory::query()->where('is_active', true)->pluck('name', 'id'))
                ->rules(['nullable', 'exists:bank_categories,id']),

            TextField::make('description')->label('Description')->type('textarea'),

            SelectField::make('is_active')
                ->label('Active')
                ->options(['1' => 'Yes', '0' => 'No'])
                ->default('1'),
        ];
    }
}
