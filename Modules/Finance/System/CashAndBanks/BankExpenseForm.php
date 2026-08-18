<?php

namespace Modules\Finance\System\CashAndBanks;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\DateField;
use App\Support\DynamicForm\Core\Fields\SelectField;
use App\Support\DynamicForm\Core\Fields\TextField;
use App\Support\LookupOptions;
use Modules\Finance\Models\CashAndBanks\BankExpenses\BankExpense;

class BankExpenseForm extends DynamicForm
{
    public function model(): string
    {
        return BankExpense::class;
    }

    public function fields(): array
    {
        return [
            SelectField::make('bank_id')
                ->label('Bank')
                ->options(LookupOptions::active('banks', 'bank_name'))
                ->required()
                ->rules(['required', 'exists:banks,id']),

            SelectField::make('bank_account_id')
                ->label('Bank Account')
                ->options(LookupOptions::active('bank_accounts', 'account_name'))
                ->rules(['nullable', 'exists:bank_accounts,id']),

            DateField::make('expense_date')
                ->label('Expense Date')
                ->required(),

            SelectField::make('expense_type')
                ->label('Expense Type')
                ->options([
                    'service_fee' => 'Service Fee',
                    'interest' => 'Interest',
                    'commission' => 'Commission',
                    'other' => 'Other',
                ])
                ->required(),

            TextField::make('amount')
                ->label('Amount')
                ->type('number')
                ->step('0.01')
                ->required()
                ->rules(['required', 'numeric', 'min:0']),

            SelectField::make('currency_id')
                ->label('Currency')
                ->options(LookupOptions::active('currencies', 'name'))
                ->required()
                ->rules(['required', 'exists:currencies,id']),

            TextField::make('description')->label('Description')->type('textarea'),

            TextField::make('invoice_reference')->label('Invoice Reference'),

            SelectField::make('gl_account_id')
                ->label('GL Account (Expense)')
                ->options(LookupOptions::active('accounts', 'name'))
                ->required()
                ->rules(['required', 'exists:accounts,id']),

            SelectField::make('status')
                ->label('Status')
                ->options(['draft' => 'Draft', 'posted' => 'Posted'])
                ->default('draft'),

            SelectField::make('reconciliation_status')
                ->label('Reconciliation Status')
                ->options(['unreconciled' => 'Unreconciled', 'reconciled' => 'Reconciled'])
                ->default('unreconciled'),
        ];
    }
}
