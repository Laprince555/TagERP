<?php

namespace Modules\Finance\System\CashAndBanks;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Models\CashAndBanks\BankExpenses\BankExpense;

class BankExpenseRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'fin-cbn-bexp';
    }

    public function modelClass(): string
    {
        return BankExpense::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'code', 'number', 'status', 'is_active'];
    }

    public function cardColumns(): array
    {
        return ['bank.bank_name', 'amount', 'expense_type', 'expense_date', 'status'];
    }

    public function previewColumns(): array
    {
        return ['amount', 'expense_type', 'status'];
    }

    public function title(Model $record): string
    {
        return sprintf('Expense #%s - %s', $record->number, ucfirst(str_replace('_', ' ', $record->expense_type)));
    }

    public function url(Model $record): ?string
    {
        return route('finance.cash-and-banks.bank-expenses.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        /** @var BankExpense $record */
        return [
            new RecordFact('Bank', $record->bank?->bank_name ?? 'N/A', 10),
            new RecordFact('Type', ucfirst(str_replace('_', ' ', $record->expense_type)), 20),
            new RecordFact('Amount', $record->amount, 30),
            new RecordFact('Date', $record->expense_date?->format('Y-m-d') ?? 'N/A', 40),
            new RecordFact('Status', ucfirst($record->status), 50),
        ];
    }

    public function previewFacts(Model $record): array
    {
        return $this->cardFacts($record);
    }

    public function scopeQuery(Builder $query): Builder
    {
        return $query->with(['bank']);
    }

    public function authorize(Model $record): bool
    {
        return true;
    }

    public function cacheTtl(): ?int
    {
        return null;
    }
}
