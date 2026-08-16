<?php

namespace Modules\Finance\System\GeneralLedger;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\SelectField;
use App\Support\DynamicForm\Core\Fields\TextareaField;
use App\Support\DynamicForm\Core\Fields\TextField;
use Modules\Finance\Models\GeneralLedger\AccountGroup;
use Modules\Finance\Models\GeneralLedger\AccountGroupPurpose;

/**
 * Create-form definition for the "fin-gl-agr" Application. Accounts and the
 * people the group is granted to are attached from its record view.
 */
class AccountGroupForm extends DynamicForm
{
    public function model(): string
    {
        return AccountGroup::class;
    }

    public function fields(): array
    {
        return [
            TextField::make('name')->label('Name')->required(),
            SelectField::make('purpose')
                ->label('Purpose')
                ->options(AccountGroupPurpose::options())
                ->required()
                ->rules(['in:'.implode(',', array_column(AccountGroupPurpose::cases(), 'value'))]),
            TextareaField::make('description')->label('Description'),
        ];
    }
}
