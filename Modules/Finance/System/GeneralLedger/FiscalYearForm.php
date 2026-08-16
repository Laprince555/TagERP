<?php

namespace Modules\Finance\System\GeneralLedger;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\DateField;
use App\Support\DynamicForm\Core\Fields\RelationListField;
use App\Support\DynamicForm\Core\Fields\TextField;
use Modules\Finance\Models\GeneralLedger\FiscalYear;
use Modules\HR\Models\OrganizationStructure\Entity;

/**
 * Create-form definition for the "fin-gl-fyr" Application. The periods are
 * generated from the year afterward rather than keyed in one by one.
 */
class FiscalYearForm extends DynamicForm
{
    public function model(): string
    {
        return FiscalYear::class;
    }

    public function fields(): array
    {
        return [
            TextField::make('name')->label('Name')->required(),
            RelationListField::make('entity')
                ->model(Entity::class)
                ->createForm('hr.organization-structure.entity.create')
                ->field('name')
                ->column('entity_id')
                ->label('Entity')
                ->required(),
            DateField::make('start_date')->label('Start Date')->required(),
            DateField::make('end_date')->label('End Date')->required()->rules(['after:start_date']),
        ];
    }
}
