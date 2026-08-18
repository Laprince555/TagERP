<?php

namespace Modules\Finance\System\CashAndBanks;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\SelectField;
use App\Support\DynamicForm\Core\Fields\TextField;
use App\Support\LookupOptions;
use Modules\Finance\Models\CashAndBanks\Banks\BankAccount;

class BankAccountForm extends DynamicForm
{
    public function model(): string
    {
        return BankAccount::class;
    }

    public function fields(): array
    {
        return [
            TextField::make('account_number')->label('Account Number')->required(),

            TextField::make('account_name')->label('Account Name')->required(),

            SelectField::make('account_type')
                ->label('Account Type')
                ->options([
                    'checking' => 'Checking',
                    'savings' => 'Savings',
                    'fixed' => 'Fixed Deposit',
                ])
                ->required(),

            SelectField::make('currency_id')
                ->label('Currency')
                ->options(LookupOptions::active('currencies', 'name'))
                ->required()
                ->rules(['required', 'exists:currencies,id']),

            TextField::make('balance')
                ->label('Current Balance')
                ->type('number')
                ->step('0.01')
                ->default('0'),

            SelectField::make('gl_account_id')
                ->label('GL Account')
                ->options(LookupOptions::active('accounts', 'name'))
                ->rules(['nullable', 'exists:accounts,id']),

            SelectField::make('is_active')
                ->label('Active')
                ->options(['1' => 'Yes', '0' => 'No'])
                ->default('1'),
        ];
    }
}
