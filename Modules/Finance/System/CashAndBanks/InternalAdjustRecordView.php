<?php

namespace Modules\Finance\System\CashAndBanks;

use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Field;
use App\Support\DynamicRecordView\Core\FieldsContent;
use App\Support\DynamicRecordView\Core\Tab;
use Modules\Finance\Models\CashAndBanks\InternalAdjust\InternalAdjust;

class InternalAdjustRecordView extends DynamicRecordView
{
    public function model(): string
    {
        return InternalAdjust::class;
    }

    public function title(mixed $record): string
    {
        return $record->number ?? $record->code ?? 'Internal Adjust';
    }

    public function subtitle(mixed $record): ?string
    {
        return "{$record->amount} - {$record->status}";
    }

    public function tabs(): array
    {
        return [
            Tab::make('Details')
                ->content(FieldsContent::make()
                    ->fields([
                        Field::make('code', 'Code'),
                        Field::make('number', 'Number'),
                        Field::make('adjustment_date', 'Adjustment Date'),
                        Field::make('amount', 'Amount'),
                        Field::make('currency_id', 'Currency')
                            ->relationDisplay('currency', 'code'),
                        Field::make('from_bank_id', 'From Bank')
                            ->relationDisplay('fromBank', 'name'),
                        Field::make('from_safe_id', 'From Safe')
                            ->relationDisplay('fromSafe', 'name'),
                        Field::make('to_bank_id', 'To Bank')
                            ->relationDisplay('toBank', 'name'),
                        Field::make('to_safe_id', 'To Safe')
                            ->relationDisplay('toSafe', 'name'),
                        Field::make('description', 'Description'),
                        Field::make('reference', 'Reference'),
                        Field::make('status', 'Status'),
                        Field::make('posted_at', 'Posted At'),
                        Field::make('journal_id', 'GL Journal')
                            ->relationDisplay('journal', 'code'),
                    ])
                ),
        ];
    }
}
