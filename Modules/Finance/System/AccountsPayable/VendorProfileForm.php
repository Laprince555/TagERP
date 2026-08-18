<?php

namespace Modules\Finance\System\AccountsPayable;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\RelationListField;
use App\Support\DynamicForm\Core\Fields\SelectField;
use App\Support\DynamicForm\Core\Fields\TextField;
use Modules\Finance\Models\AccountsPayable\VendorFinancialRole;
use Modules\Finance\Models\AccountsPayable\VendorProfile;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\General\Models\World\Currency;
use Modules\Procurement\Models\Vendors\Vendor;

/**
 * Create-form definition for the "fin-ap-vpr" Application.
 */
class VendorProfileForm extends DynamicForm
{
    public function model(): string
    {
        return VendorProfile::class;
    }

    public function fields(): array
    {
        return [
            RelationListField::make('vendor')
                ->model(Vendor::class)
                ->createForm('procurement.vendor-management.vendor.create')
                ->field('code')
                ->column('vendor_id')
                ->label('Vendor')
                ->required(),
            SelectField::make('financial_role')
                ->label('Financial Role')
                ->options(VendorFinancialRole::options())
                ->required()
                ->rules(['in:'.implode(',', array_column(VendorFinancialRole::cases(), 'value'))]),
            RelationListField::make('currency')
                ->model(Currency::class)
                ->field('name')
                ->column('currency_id')
                ->label('Currency')
                ->required(),
            RelationListField::make('apAccount')
                ->model(Account::class)
                ->field('name')
                ->column('ap_account_id')
                ->label('AP Control Account')
                ->required(),
            TextField::make('payment_terms_days')
                ->type('number')
                ->label('Payment Terms (Days)')
                ->required()
                ->rules(['integer', 'min:0']),
        ];
    }
}
