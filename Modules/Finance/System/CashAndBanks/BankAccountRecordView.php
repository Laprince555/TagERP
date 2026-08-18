<?php

namespace Modules\Finance\System\CashAndBanks;

use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Field;
use App\Support\DynamicRecordView\Core\FieldsContent;
use App\Support\DynamicRecordView\Core\Tab;
use Modules\Finance\Models\CashAndBanks\Banks\BankAccount;

class BankAccountRecordView extends DynamicRecordView
{
    public function model(): string
    {
        return BankAccount::class;
    }

    public function title(mixed $record): string
    {
        return $record->account_name ?? 'Bank Account';
    }

    public function subtitle(mixed $record): ?string
    {
        return $record->account_number;
    }

    public function tabs(): array
    {
        return [
            Tab::make('Details')
                ->content(FieldsContent::make()
                    ->fields([
                        Field::make('account_name', 'Account Name'),
                        Field::make('account_number', 'Account Number'),
                        Field::make('account_type', 'Account Type'),
                        Field::make('code', 'Code'),
                        Field::make('bank_id', 'Bank')
                            ->relationDisplay('bank', 'name'),
                        Field::make('currency_id', 'Currency')
                            ->relationDisplay('currency', 'code'),
                        Field::make('balance', 'Balance'),
                        Field::make('gl_account_id', 'GL Account')
                            ->relationDisplay('glAccount', 'name'),
                        Field::make('is_active', 'Active')->boolean(),
                    ])
                ),
        ];
    }
}
