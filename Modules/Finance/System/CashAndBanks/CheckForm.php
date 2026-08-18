<?php

namespace Modules\Finance\System\CashAndBanks;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\DateField;
use App\Support\DynamicForm\Core\Fields\SelectField;
use App\Support\DynamicForm\Core\Fields\TextField;
use App\Support\LookupOptions;
use Modules\Finance\Models\CashAndBanks\Banks\Check;

class CheckForm extends DynamicForm
{
    public function model(): string
    {
        return Check::class;
    }

    public function fields(): array
    {
        return [
            SelectField::make('checks_book_id')
                ->label('Checks Book')
                ->options(LookupOptions::active('checks_books', 'code'))
                ->required()
                ->rules(['required', 'exists:checks_books,id']),

            TextField::make('check_number')
                ->label('Check Number')
                ->type('number')
                ->required()
                ->rules(['required', 'integer']),

            DateField::make('check_date')
                ->label('Check Date')
                ->required(),

            TextField::make('payee_name')
                ->label('Payee Name')
                ->required(),

            TextField::make('amount')
                ->label('Amount')
                ->type('number')
                ->step('0.01')
                ->required()
                ->rules(['required', 'numeric', 'min:0']),

            TextField::make('description')->label('Description')->type('textarea'),

            SelectField::make('status')
                ->label('Status')
                ->options([
                    'written' => 'Written',
                    'issued' => 'Issued',
                    'cleared' => 'Cleared',
                    'bounced' => 'Bounced',
                    'cancelled' => 'Cancelled',
                ])
                ->default('written'),
        ];
    }
}
