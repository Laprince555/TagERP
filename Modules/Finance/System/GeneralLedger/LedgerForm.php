<?php

namespace Modules\Finance\System\GeneralLedger;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\RelationListField;
use App\Support\DynamicForm\Core\Fields\SelectField;
use App\Support\DynamicForm\Core\Fields\TextField;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\Finance\Models\GeneralLedger\Chart;
use Modules\Finance\Models\GeneralLedger\Ledger;
use Modules\Finance\Models\GeneralLedger\LedgerConversionType;
use Modules\Finance\Models\GeneralLedger\RateType;
use Modules\General\Models\World\Currency;
use Modules\HR\Models\OrganizationStructure\Entity;

/**
 * Create-form definition for the "fin-gl-led" Application.
 *
 * The primary/secondary combinations are validated in Ledger::saving() rather
 * than here, so the rule holds for every writer — seeders, jobs and the
 * replication engine included — not just this form.
 */
class LedgerForm extends DynamicForm
{
    public function model(): string
    {
        return Ledger::class;
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
            RelationListField::make('chart')
                ->model(Chart::class)
                ->createForm('finance.general-ledger.chart.create')
                ->field('name')
                ->column('chart_id')
                ->label('Chart of Accounts')
                ->required(),
            RelationListField::make('baseCurrency')
                ->model(Currency::class)
                ->field('name')
                ->column('base_currency_id')
                ->label('Base Currency')
                ->required(),
            SelectField::make('is_primary')
                ->label('Primary Ledger')
                ->options(['1' => 'Yes', '0' => 'No'])
                ->rules(['boolean']),
            RelationListField::make('primaryLedger')
                ->model(Ledger::class)
                ->createForm('finance.general-ledger.ledger.create')
                ->field('name')
                ->column('primary_ledger_id')
                ->label('Fed From'),
            SelectField::make('conversion_type')
                ->label('Differs By')
                ->options(LedgerConversionType::options())
                ->rules(['nullable', 'in:'.implode(',', array_column(LedgerConversionType::cases(), 'value'))]),
            SelectField::make('rate_type')
                ->label('Rate Type')
                ->options(RateType::options())
                ->rules(['in:'.implode(',', array_column(RateType::cases(), 'value'))]),
            RelationListField::make('roundingAccount')
                ->model(Account::class)
                ->createForm('finance.general-ledger.account.create')
                ->field('name')
                ->column('rounding_account_id')
                ->label('Rounding Difference Account'),
        ];
    }
}
