<?php

namespace Modules\Finance\System\CashAndBanks;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\FormField;
use Modules\Finance\Models\CashAndBanks\PaymentDisburse\PaymentDisbursementRequest;

class PaymentDisbursementForm extends DynamicForm
{
    public function model(): string
    {
        return PaymentDisbursementRequest::class;
    }

    public function title(): string
    {
        return 'Payment Disbursement Request';
    }

    public function fields(): array
    {
        return [
            FormField::make('entity_id')->label('Entity')->required()->relationship('entity', 'name'),
            FormField::make('payment_date')->label('Payment Date')->required()->type('date'),
            FormField::make('amount')->label('Amount')->required()->type('decimal'),
            FormField::make('currency_id')->label('Currency')->required()->relationship('currency', 'code'),
            FormField::make('payment_method')->label('Payment Method')->required()
                ->options([
                    'bank_transfer' => 'Bank Transfer',
                    'check' => 'Check',
                    'cash' => 'Cash',
                ]),
            FormField::make('from_bank_id')->label('From Bank')->relationship('fromBank', 'bank_name'),
            FormField::make('from_safe_id')->label('From Safe')->relationship('fromSafe', 'name'),
            FormField::make('description')->label('Description')->type('textarea'),
        ];
    }
}
