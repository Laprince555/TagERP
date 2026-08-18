<?php

namespace Modules\Finance\System\CashAndBanks;

use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\RecordViewField;
use Modules\Finance\Models\CashAndBanks\Collection\CollectionRequest;

class CollectionRecordView extends DynamicRecordView
{
    public function model(): string
    {
        return CollectionRequest::class;
    }

    public function title(): string
    {
        return 'Collection Request';
    }

    public function subtitle(): ?string
    {
        return 'Manage customer collection requests';
    }

    public function fields(): array
    {
        return [
            RecordViewField::make('code')->label('Code'),
            RecordViewField::make('number')->label('Number'),
            RecordViewField::make('entity.name')->label('Entity'),
            RecordViewField::make('expected_date')->label('Expected Date')->date(),
            RecordViewField::make('collection_date')->label('Collection Date')->date(),
            RecordViewField::make('amount')->label('Amount')->decimal(),
            RecordViewField::make('currency.code')->label('Currency'),
            RecordViewField::make('collection_method')->label('Collection Method'),
            RecordViewField::make('to_bank.bank_name')->label('To Bank'),
            RecordViewField::make('to_safe.name')->label('To Safe'),
            RecordViewField::make('status')->label('Status')->badge(),
            RecordViewField::make('description')->label('Description')->textarea(),
        ];
    }

    public function tabs(): array
    {
        return [
            [
                'label' => 'Details',
                'fields' => ['code', 'number', 'entity.name', 'expected_date', 'collection_date', 'amount', 'currency.code'],
            ],
            [
                'label' => 'Collection Info',
                'fields' => ['collection_method', 'to_bank.bank_name', 'to_safe.name', 'status', 'description'],
            ],
        ];
    }
}
