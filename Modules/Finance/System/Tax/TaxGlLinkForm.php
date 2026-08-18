<?php

namespace Modules\Finance\System\Tax;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\RelationListField;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\Finance\Models\Tax\Tax;
use Modules\Finance\Models\Tax\TaxCategory;
use Modules\Finance\Models\Tax\TaxGlLink;

/**
 * Create-form definition for the "fin-tax-gll" Application. Leaving Tax
 * Category or Tax empty makes this rule that dimension's fallback.
 */
class TaxGlLinkForm extends DynamicForm
{
    public function model(): string
    {
        return TaxGlLink::class;
    }

    public function fields(): array
    {
        return [
            RelationListField::make('taxCategory')
                ->model(TaxCategory::class)
                ->field('name')
                ->column('tax_category_id')
                ->label('Tax Category (blank = any)'),
            RelationListField::make('tax')
                ->model(Tax::class)
                ->field('name')
                ->column('tax_id')
                ->label('Tax (blank = any)'),
            RelationListField::make('account')
                ->model(Account::class)
                ->field('name')
                ->column('account_id')
                ->label('GL Account')
                ->required(),
        ];
    }
}
