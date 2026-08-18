<?php

namespace Modules\Finance\System\CashAndBanks;

use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Field;
use App\Support\DynamicRecordView\Core\FieldsContent;
use App\Support\DynamicRecordView\Core\Tab;
use Modules\Finance\Models\CashAndBanks\BankExpenses\BankExpense;

class BankExpenseRecordView extends DynamicRecordView
{
    public function model(): string
    {
        return BankExpense::class;
    }

    public function title(mixed $record): string
    {
        return $record->number ?? $record->code ?? 'Bank Expense';
    }

    public function subtitle(mixed $record): ?string
    {
        return "{$record->amount} - {$record->expense_type}";
    }

    public function tabs(): array
    {
        return [
            Tab::make('Details')
                ->content(FieldsContent::make()
                    ->fields([
                        Field::make('code', 'Code'),
                        Field::make('number', 'Number'),
                        Field::make('expense_date', 'Expense Date'),
                        Field::make('amount', 'Amount'),
                        Field::make('currency_id', 'Currency')
                            ->relationDisplay('currency', 'code'),
                        Field::make('expense_type', 'Expense Type'),
                        Field::make('description', 'Description'),
                        Field::make('invoice_reference', 'Invoice Reference'),
                        Field::make('bank_id', 'Bank')
                            ->relationDisplay('bank', 'name'),
                        Field::make('bank_account_id', 'Bank Account')
                            ->relationDisplay('bankAccount', 'account_name'),
                        Field::make('gl_account_id', 'GL Account')
                            ->relationDisplay('glAccount', 'name'),
                        Field::make('status', 'Status'),
                        Field::make('reconciliation_status', 'Reconciliation Status'),
                        Field::make('posted_at', 'Posted At'),
                        Field::make('journal_id', 'GL Journal')
                            ->relationDisplay('journal', 'code'),
                    ])
                ),
        ];
    }
}
