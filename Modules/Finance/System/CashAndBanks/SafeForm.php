<?php

namespace Modules\Finance\System\CashAndBanks;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\SelectField;
use App\Support\DynamicForm\Core\Fields\TextField;
use App\Support\LookupOptions;
use Modules\Finance\Models\CashAndBanks\Safes\Safe;

class SafeForm extends DynamicForm
{
    public function model(): string
    {
        return Safe::class;
    }

    public function fields(): array
    {
        return [
            TextField::make('name')->label('Safe Name')->required(),

            SelectField::make('entity_id')
                ->label('Entity')
                ->options(LookupOptions::active('entities', 'name'))
                ->required()
                ->rules(['required', 'exists:entities,id']),

            SelectField::make('employee_id')
                ->label('Responsible Employee')
                ->options(LookupOptions::active('employees', 'display_name'))
                ->rules(['nullable', 'exists:employees,id']),

            TextField::make('location')->label('Location'),

            TextField::make('description')->label('Description')->type('textarea'),

            SelectField::make('gl_account_id')
                ->label('GL Account (Cash)')
                ->options(LookupOptions::active('accounts', 'name'))
                ->rules(['nullable', 'exists:accounts,id']),

            SelectField::make('is_active')
                ->label('Active')
                ->options(['1' => 'Yes', '0' => 'No'])
                ->default('1'),
        ];
    }
}
