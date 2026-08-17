<?php

namespace Modules\Finance\System\CashAndBanks;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\FormField;
use Modules\Finance\Models\CashAndBanks\Collection\CollectionRequest;

class CollectionForm extends DynamicForm
{
    public function model(): string
    {
        return CollectionRequest::class;
    }

    public function title(): string
    {
        return 'Collection Request';
    }

    public function fields(): array
    {
        return [
            FormField::make('entity_id')->label('Entity')->required()->relationship('entity', 'name'),
            FormField::make('expected_date')->label('Expected Date')->required()->type('date'),
            FormField::make('collection_date')->label('Collection Date')->type('date'),
            FormField::make('amount')->label('Amount')->required()->type('decimal'),
            FormField::make('currency_id')->label('Currency')->required()->relationship('currency', 'code'),
            FormField::make('collection_method')->label('Collection Method')->required()
                ->options([
                    'bank_transfer' => 'Bank Transfer',
                    'check' => 'Check',
                    'cash' => 'Cash',
                    'other' => 'Other',
                ]),
            FormField::make('to_bank_id')->label('To Bank')->relationship('toBank', 'bank_name'),
            FormField::make('to_safe_id')->label('To Safe')->relationship('toSafe', 'name'),
            FormField::make('description')->label('Description')->type('textarea'),
        ];
    }
}
