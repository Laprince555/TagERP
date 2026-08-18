<?php

namespace Modules\Finance\System\CashAndBanks;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Models\CashAndBanks\Banks\BankAccount;

class BankAccountRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'fin-cbn-bnk-bacc';
    }

    public function modelClass(): string
    {
        return BankAccount::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'code', 'account_number', 'account_name', 'is_active'];
    }

    public function cardColumns(): array
    {
        return ['account_type', 'currency.code', 'balance'];
    }

    public function previewColumns(): array
    {
        return ['account_type', 'balance'];
    }

    public function title(Model $record): string
    {
        return (string) $record->account_name;
    }

    public function url(Model $record): ?string
    {
        $bankId = $record->bank_id;

        return route('finance.cash-and-banks.banks.accounts.show', ['bankId' => $bankId, 'recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        /** @var BankAccount $record */
        return [
            new RecordFact('Account Number', $record->account_number, 10),
            new RecordFact('Type', ucfirst($record->account_type), 20),
            new RecordFact('Currency', $record->currency?->code ?? 'N/A', 30),
            new RecordFact('Balance', $record->balance, 40),
        ];
    }

    public function previewFacts(Model $record): array
    {
        return $this->cardFacts($record);
    }

    public function scopeQuery(Builder $query): Builder
    {
        return $query->where('is_active', true)->with(['currency']);
    }

    public function authorize(Model $record): bool
    {
        /** @var BankAccount $record */
        return (bool) $record->is_active;
    }

    public function cacheTtl(): ?int
    {
        return null;
    }
}
