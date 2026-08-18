<?php

namespace Modules\Finance\System\CashAndBanks;

use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\RecordViewField;
use Modules\Finance\Models\CashAndBanks\PaymentDisburse\PaymentDisbursementRequest;

class PaymentDisbursementRecordView extends DynamicRecordView
{
    public function model(): string
    {
        return PaymentDisbursementRequest::class;
    }

    public function title(): string
    {
        return 'Payment Disbursement Request';
    }

    public function subtitle(): ?string
    {
        return 'Manage payment disbursement requests';
    }

    public function fields(): array
    {
        return [
            RecordViewField::make('code')->label('Code'),
            RecordViewField::make('number')->label('Number'),
            RecordViewField::make('entity.name')->label('Entity'),
            RecordViewField::make('payment_date')->label('Payment Date')->date(),
            RecordViewField::make('amount')->label('Amount')->decimal(),
            RecordViewField::make('currency.code')->label('Currency'),
            RecordViewField::make('payment_method')->label('Payment Method'),
            RecordViewField::make('from_bank.bank_name')->label('From Bank'),
            RecordViewField::make('from_safe.name')->label('From Safe'),
            RecordViewField::make('status')->label('Status')->badge(),
            RecordViewField::make('description')->label('Description')->textarea(),
        ];
    }

    public function tabs(): array
    {
        return [
            [
                'label' => 'Details',
                'fields' => ['code', 'number', 'entity.name', 'payment_date', 'amount', 'currency.code'],
            ],
            [
                'label' => 'Payment Info',
                'fields' => ['payment_method', 'from_bank.bank_name', 'from_safe.name', 'status', 'description'],
            ],
        ];
    }
}
