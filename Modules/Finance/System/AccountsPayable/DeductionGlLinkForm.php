<?php

namespace Modules\Finance\System\AccountsPayable;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\RelationListField;
use Modules\Finance\Models\AccountsPayable\Deduction;
use Modules\Finance\Models\AccountsPayable\DeductionCategory;
use Modules\Finance\Models\AccountsPayable\DeductionGlLink;
use Modules\Finance\Models\GeneralLedger\Account;

/**
 * Create-form definition for the "fin-ap-dgl" Application. Leaving
 * Deduction Category or Deduction empty makes this rule that dimension's
 * fallback.
 */
class DeductionGlLinkForm extends DynamicForm
{
    public function model(): string
    {
        return DeductionGlLink::class;
    }

    public function fields(): array
    {
        return [
            RelationListField::make('deductionCategory')
                ->model(DeductionCategory::class)
                ->field('name')
                ->column('deduction_category_id')
                ->label('Deduction Category (blank = any)'),
            RelationListField::make('deduction')
                ->model(Deduction::class)
                ->field('name')
                ->column('deduction_id')
                ->label('Deduction (blank = any)'),
            RelationListField::make('account')
                ->model(Account::class)
                ->field('name')
                ->column('account_id')
                ->label('GL Account')
                ->required(),
        ];
    }
}
