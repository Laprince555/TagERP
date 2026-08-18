<?php

namespace Modules\Finance\System\CashAndBanks;

use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Field;
use App\Support\DynamicRecordView\Core\FieldsContent;
use App\Support\DynamicRecordView\Core\Tab;
use Modules\Finance\Models\CashAndBanks\Banks\ChecksBook;

class ChecksBookRecordView extends DynamicRecordView
{
    public function model(): string
    {
        return ChecksBook::class;
    }

    public function title(mixed $record): string
    {
        return "Checks {$record->check_series_start}-{$record->check_series_end}";
    }

    public function subtitle(mixed $record): ?string
    {
        return "Current: {$record->current_check_number}";
    }

    public function tabs(): array
    {
        return [
            Tab::make('Details')
                ->content(FieldsContent::make()
                    ->fields([
                        Field::make('code', 'Code'),
                        Field::make('bank_id', 'Bank')
                            ->relationDisplay('bank', 'name'),
                        Field::make('bank_account_id', 'Bank Account')
                            ->relationDisplay('bankAccount', 'account_name'),
                        Field::make('check_series_start', 'Series Start'),
                        Field::make('check_series_end', 'Series End'),
                        Field::make('current_check_number', 'Current Check Number'),
                        Field::make('status', 'Status'),
                        Field::make('is_active', 'Active')->boolean(),
                    ])
                ),
        ];
    }
}
