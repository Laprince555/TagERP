<?php

namespace Modules\Finance\System\GeneralLedger;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\DateField;
use App\Support\DynamicForm\Core\Fields\RelationListField;
use App\Support\DynamicForm\Core\Fields\SelectField;
use App\Support\DynamicForm\Core\Fields\TextField;
use Modules\Finance\Models\GeneralLedger\ExchangeRate;
use Modules\Finance\Models\GeneralLedger\RateType;
use Modules\General\Models\World\Currency;

/**
 * Create-form definition for the "fin-gl-rat" Application.
 */
class ExchangeRateForm extends DynamicForm
{
    public function model(): string
    {
        return ExchangeRate::class;
    }

    public function fields(): array
    {
        return [
            RelationListField::make('fromCurrency')
                ->model(Currency::class)
                ->field('name')
                ->column('from_currency_id')
                ->label('From Currency')
                ->required(),
            RelationListField::make('toCurrency')
                ->model(Currency::class)
                ->field('name')
                ->column('to_currency_id')
                ->label('To Currency')
                ->required(),
            DateField::make('rate_date')->label('Rate Date')->required(),
            TextField::make('rate')
                ->type('number')
                ->label('Rate')
                ->required()
                ->rules(['numeric', 'gt:0']),
            SelectField::make('rate_type')
                ->label('Rate Type')
                ->options(RateType::options())
                ->required()
                ->rules(['in:'.implode(',', array_column(RateType::cases(), 'value'))]),
        ];
    }
}
