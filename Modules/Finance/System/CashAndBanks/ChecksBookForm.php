<?php

namespace Modules\Finance\System\CashAndBanks;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\SelectField;
use App\Support\DynamicForm\Core\Fields\TextField;
use App\Support\LookupOptions;
use Modules\Finance\Models\CashAndBanks\Banks\ChecksBook;

class ChecksBookForm extends DynamicForm
{
    public function model(): string
    {
        return ChecksBook::class;
    }

    public function fields(): array
    {
        return [
            SelectField::make('bank_account_id')
                ->label('Bank Account')
                ->options(LookupOptions::active('bank_accounts', 'account_name'))
                ->required()
                ->rules(['required', 'exists:bank_accounts,id']),

            TextField::make('check_series_start')
                ->label('Start Check Number')
                ->type('number')
                ->required()
                ->rules(['required', 'integer', 'min:1']),

            TextField::make('check_series_end')
                ->label('End Check Number')
                ->type('number')
                ->required()
                ->rules(['required', 'integer', 'min:1']),

            TextField::make('current_check_number')
                ->label('Current Check Number')
                ->type('number')
                ->default('0'),

            SelectField::make('status')
                ->label('Status')
                ->options(['active' => 'Active', 'closed' => 'Closed'])
                ->default('active'),

            SelectField::make('is_active')
                ->label('Active')
                ->options(['1' => 'Yes', '0' => 'No'])
                ->default('1'),
        ];
    }
}
