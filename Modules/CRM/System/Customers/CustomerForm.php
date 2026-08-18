<?php

namespace Modules\CRM\System\Customers;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\RelationListField;
use App\Support\DynamicForm\Core\Fields\SelectField;
use Modules\CRM\Models\Customers\Customer;
use Modules\CRM\Models\Customers\CustomerType;
use Modules\Finance\Models\AccountsReceivable\CustomerCategory;
use Modules\General\Models\World\Companies\Company;
use Modules\General\Models\World\Currency;

/**
 * Create-form definition for the "crm-cust-cus" Application.
 */
class CustomerForm extends DynamicForm
{
    public function model(): string
    {
        return Customer::class;
    }

    public function fields(): array
    {
        return [
            RelationListField::make('company')
                ->model(Company::class)
                ->field('name')
                ->column('company_id')
                ->label('Company')
                ->required(),
            RelationListField::make('category')
                ->model(CustomerCategory::class)
                ->field('name')
                ->column('customer_category_id')
                ->label('Customer Category'),
            SelectField::make('customer_type')
                ->label('Customer Type')
                ->options(CustomerType::options())
                ->required()
                ->rules(['in:'.implode(',', array_column(CustomerType::cases(), 'value'))]),
            RelationListField::make('defaultCurrency')
                ->model(Currency::class)
                ->field('name')
                ->column('default_currency_id')
                ->label('Default Currency'),
        ];
    }
}
