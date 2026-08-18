<?php

namespace Modules\Finance\System\AccountsPayable;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\DateField;
use App\Support\DynamicForm\Core\Fields\RelationListField;
use App\Support\DynamicForm\Core\Fields\TextField;
use Modules\Finance\Models\AccountsPayable\ApInvoice;
use Modules\Finance\Models\AccountsPayable\VendorProfile;
use Modules\General\Models\World\Currency;
use Modules\HR\Models\OrganizationStructure\Branch;
use Modules\HR\Models\OrganizationStructure\Entity;

/**
 * Create-form definition for the "fin-ap-inv" Application. Totals are not
 * entered here — they are computed from lines on the record view, so this
 * form only captures what a clerk actually knows before typing any lines.
 */
class ApInvoiceForm extends DynamicForm
{
    public function model(): string
    {
        return ApInvoice::class;
    }

    public function fields(): array
    {
        return [
            RelationListField::make('entity')
                ->model(Entity::class)
                ->field('name')
                ->column('entity_id')
                ->label('Entity')
                ->required(),
            RelationListField::make('branch')
                ->model(Branch::class)
                ->field('name')
                ->column('branch_id')
                ->label('Branch')
                ->required(),
            RelationListField::make('vendorProfile')
                ->model(VendorProfile::class)
                ->createForm('finance.accounts-payable.vendor-profile.create')
                ->field('code')
                ->column('vendor_profile_id')
                ->label('Vendor Profile')
                ->required(),
            TextField::make('invoice_number')->label('Invoice Number')->required()->rules(['max:100']),
            DateField::make('issue_date')->label('Issue Date')->required(),
            DateField::make('due_date')->label('Due Date'),
            RelationListField::make('currency')
                ->model(Currency::class)
                ->field('name')
                ->column('currency_id')
                ->label('Currency')
                ->required(),
            TextField::make('po_reference')->label('PO Reference')->rules(['max:100']),
            TextField::make('subtotal')->type('number')->label('Subtotal')->required()->rules(['numeric', 'min:0']),
            TextField::make('tax_amount')->type('number')->label('Tax Amount')->required()->rules(['numeric', 'min:0']),
            TextField::make('total_amount')->type('number')->label('Total Amount')->required()->rules(['numeric', 'min:0']),
        ];
    }
}
