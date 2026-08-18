<?php

namespace Modules\Finance\System\CashAndBanks;

use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Field;
use App\Support\DynamicRecordView\Core\FieldsContent;
use App\Support\DynamicRecordView\Core\Tab;
use Modules\Finance\Models\CashAndBanks\Banks\Bank;

class BankRecordView extends DynamicRecordView
{
    public function model(): string
    {
        return Bank::class;
    }

    public function title(mixed $record): string
    {
        return $record->name ?? 'Bank';
    }

    public function subtitle(mixed $record): ?string
    {
        return $record->bank_code;
    }

    public function tabs(): array
    {
        return [
            Tab::make('Details')
                ->content(FieldsContent::make()
                    ->fields([
                        Field::make('name', 'Bank Name'),
                        Field::make('code', 'Code'),
                        Field::make('category_id', 'Category')
                            ->relationDisplay('category', 'name'),
                        Field::make('entity_id', 'Entity')
                            ->relationDisplay('entity', 'name'),
                        Field::make('bank_code', 'Bank Code'),
                        Field::make('bank_name', 'Bank Name (Official)'),
                        Field::make('swift_code', 'SWIFT Code'),
                        Field::make('iban', 'IBAN'),
                        Field::make('default_gl_account_id', 'Default GL Account')
                            ->relationDisplay('defaultGlAccount', 'name'),
                        Field::make('is_active', 'Active')->boolean(),
                    ])
                ),
        ];
    }
}
