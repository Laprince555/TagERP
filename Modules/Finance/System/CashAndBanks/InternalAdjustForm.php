<?php

namespace Modules\Finance\System\CashAndBanks;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\DateField;
use App\Support\DynamicForm\Core\Fields\SelectField;
use App\Support\DynamicForm\Core\Fields\TextField;
use App\Support\LookupOptions;
use Modules\Finance\Models\CashAndBanks\InternalAdjust\InternalAdjust;

class InternalAdjustForm extends DynamicForm
{
    public function model(): string
    {
        return InternalAdjust::class;
    }

    public function fields(): array
    {
        return [
            SelectField::make('from_bank_id')
                ->label('From Bank')
                ->options(LookupOptions::active('banks', 'bank_name'))
                ->rules(['nullable', 'exists:banks,id']),

            SelectField::make('from_safe_id')
                ->label('From Safe')
                ->options(LookupOptions::active('safes', 'name'))
                ->rules(['nullable', 'exists:safes,id']),

            SelectField::make('to_bank_id')
                ->label('To Bank')
                ->options(LookupOptions::active('banks', 'bank_name'))
                ->rules(['nullable', 'exists:banks,id']),

            SelectField::make('to_safe_id')
                ->label('To Safe')
                ->options(LookupOptions::active('safes', 'name'))
                ->rules(['nullable', 'exists:safes,id']),

            DateField::make('adjustment_date')
                ->label('Adjustment Date')
                ->required(),

            TextField::make('amount')
                ->label('Amount')
                ->type('number')
                ->step('0.01')
                ->required()
                ->rules(['required', 'numeric', 'min:0']),

            SelectField::make('currency_id')
                ->label('Currency')
                ->options(LookupOptions::active('currencies', 'name'))
                ->required()
                ->rules(['required', 'exists:currencies,id']),

            TextField::make('description')->label('Description')->type('textarea'),

            TextField::make('reference')->label('Reference'),

            SelectField::make('status')
                ->label('Status')
                ->options(['draft' => 'Draft', 'posted' => 'Posted'])
                ->default('draft'),
        ];
    }
}
