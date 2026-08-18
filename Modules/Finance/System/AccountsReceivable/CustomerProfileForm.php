<?php

namespace Modules\Finance\System\AccountsReceivable;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\RelationListField;
use App\Support\DynamicForm\Core\Fields\SelectField;
use App\Support\DynamicForm\Core\Fields\TextField;
use Modules\CRM\Models\Customers\Customer;
use Modules\Finance\Models\AccountsReceivable\CustomerFinancialRole;
use Modules\Finance\Models\AccountsReceivable\CustomerProfile;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\General\Models\World\Currency;

/**
 * Create-form definition for the "fin-ar-cpr" Application.
 */
class CustomerProfileForm extends DynamicForm
{
    public function model(): string
    {
        return CustomerProfile::class;
    }

    public function fields(): array
    {
        return [
            RelationListField::make('customer')
                ->model(Customer::class)
                ->createForm('crm.customer-management.customer.create')
                ->field('code')
                ->column('customer_id')
                ->label('Customer')
                ->required(),
            SelectField::make('financial_role')
                ->label('Financial Role')
                ->options(CustomerFinancialRole::options())
                ->required()
                ->rules(['in:'.implode(',', array_column(CustomerFinancialRole::cases(), 'value'))]),
            RelationListField::make('currency')
                ->model(Currency::class)
                ->field('name')
                ->column('currency_id')
                ->label('Currency')
                ->required(),
            RelationListField::make('arAccount')
                ->model(Account::class)
                ->field('name')
                ->column('ar_account_id')
                ->label('AR Control Account')
                ->required(),
            TextField::make('credit_terms_days')
                ->type('number')
                ->label('Credit Terms (Days)')
                ->required()
                ->rules(['integer', 'min:0']),
            TextField::make('credit_limit')
                ->type('number')
                ->step('0.01')
                ->label('Credit Limit')
                ->rules(['numeric', 'min:0']),
        ];
    }
}
