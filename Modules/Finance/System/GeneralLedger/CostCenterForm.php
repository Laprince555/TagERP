<?php

namespace Modules\Finance\System\GeneralLedger;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\RelationListField;
use App\Support\DynamicForm\Core\Fields\SelectField;
use App\Support\DynamicForm\Core\Fields\TextField;
use Modules\Finance\Models\GeneralLedger\CostCenter;

/**
 * Create-form definition for the "fin-gl-cct" Application.
 */
class CostCenterForm extends DynamicForm
{
    public function model(): string
    {
        return CostCenter::class;
    }

    public function fields(): array
    {
        return [
            TextField::make('number')->label('Number')->required()->rules(['max:50']),
            TextField::make('name')->label('Name')->required(),
            RelationListField::make('parent')
                ->model(CostCenter::class)
                ->createForm('finance.general-ledger.cost-center.create')
                ->field('name')
                ->column('parent_id')
                ->label('Parent Cost Centre'),
            SelectField::make('accepts_transactions')
                ->label('Accepts Transactions')
                ->options(['1' => 'Yes', '0' => 'No'])
                ->rules(['boolean']),
        ];
    }
}
