<?php

namespace Modules\Finance\System\CashAndBanks;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\SelectField;
use App\Support\DynamicForm\Core\Fields\TextField;
use App\Support\LookupOptions;
use Modules\Finance\Models\CashAndBanks\Banks\Bank;

class BankForm extends DynamicForm
{
    public function model(): string
    {
        return Bank::class;
    }

    public function fields(): array
    {
        return [
            TextField::make('bank_name')->label('Bank Name')->required(),

            SelectField::make('category_id')
                ->label('Category')
                ->options(LookupOptions::active('bank_categories', 'name'))
                ->required()
                ->rules(['required', 'exists:bank_categories,id']),

            SelectField::make('entity_id')
                ->label('Entity')
                ->options(LookupOptions::active('entities', 'name'))
                ->required()
                ->rules(['required', 'exists:entities,id']),

            TextField::make('bank_code')->label('Bank Code'),

            TextField::make('swift_code')->label('SWIFT Code'),

            TextField::make('iban')->label('IBAN'),

            SelectField::make('default_gl_account_id')
                ->label('Default GL Account')
                ->options(LookupOptions::active('accounts', 'name'))
                ->rules(['nullable', 'exists:accounts,id']),

            SelectField::make('is_active')
                ->label('Active')
                ->options(['1' => 'Yes', '0' => 'No'])
                ->default('1'),
        ];
    }
}
