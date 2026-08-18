<?php

namespace Modules\Procurement\System\Vendors;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\RelationListField;
use App\Support\DynamicForm\Core\Fields\SelectField;
use Modules\General\Models\World\Companies\Company;
use Modules\General\Models\World\Currency;
use Modules\Procurement\Models\Vendors\Vendor;
use Modules\Procurement\Models\Vendors\VendorType;

/**
 * Create-form definition for the "proc-ven-vnd" Application.
 */
class VendorForm extends DynamicForm
{
    public function model(): string
    {
        return Vendor::class;
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
            SelectField::make('vendor_type')
                ->label('Vendor Type')
                ->options(VendorType::options())
                ->required()
                ->rules(['in:'.implode(',', array_column(VendorType::cases(), 'value'))]),
            RelationListField::make('defaultCurrency')
                ->model(Currency::class)
                ->field('name')
                ->column('default_currency_id')
                ->label('Default Currency'),
            SelectField::make('requires_po')
                ->label('Requires Purchase Order')
                ->options(['1' => 'Yes', '0' => 'No'])
                ->required()
                ->rules(['boolean']),
        ];
    }
}
