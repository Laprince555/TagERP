<?php

namespace Modules\Finance\System\CashAndBanks;

use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Field;
use App\Support\DynamicRecordView\Core\FieldsContent;
use App\Support\DynamicRecordView\Core\Tab;
use Modules\Finance\Models\CashAndBanks\Banks\Check;

class CheckRecordView extends DynamicRecordView
{
    public function model(): string
    {
        return Check::class;
    }

    public function title(mixed $record): string
    {
        return "Check #{$record->check_number}";
    }

    public function subtitle(mixed $record): ?string
    {
        return "{$record->amount} - {$record->payee_name}";
    }

    public function tabs(): array
    {
        return [
            Tab::make('Details')
                ->content(FieldsContent::make()
                    ->fields([
                        Field::make('code', 'Code'),
                        Field::make('check_number', 'Check Number'),
                        Field::make('check_date', 'Check Date'),
                        Field::make('amount', 'Amount'),
                        Field::make('payee_name', 'Payee Name'),
                        Field::make('description', 'Description'),
                        Field::make('status', 'Status'),
                        Field::make('bank_id', 'Bank')
                            ->relationDisplay('bank', 'name'),
                        Field::make('checks_book_id', 'Checks Book')
                            ->relationDisplay('checksBook', 'code'),
                        Field::make('journal_id', 'GL Journal')
                            ->relationDisplay('journal', 'code'),
                    ])
                ),
        ];
    }
}
