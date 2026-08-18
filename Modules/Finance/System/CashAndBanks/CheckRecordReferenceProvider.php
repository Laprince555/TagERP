<?php

namespace Modules\Finance\System\CashAndBanks;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Models\CashAndBanks\Banks\Check;

class CheckRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'fin-cbn-bnk-chk';
    }

    public function modelClass(): string
    {
        return Check::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'code', 'check_number', 'is_active'];
    }

    public function cardColumns(): array
    {
        return ['payee_name', 'amount', 'check_date', 'status'];
    }

    public function previewColumns(): array
    {
        return ['payee_name', 'amount', 'status'];
    }

    public function title(Model $record): string
    {
        return (string) $record->check_number;
    }

    public function url(Model $record): ?string
    {
        $bankId = $record->bank_id;

        return route('finance.cash-and-banks.banks.checks.show', ['bankId' => $bankId, 'recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        /** @var Check $record */
        return [
            new RecordFact('Payee', $record->payee_name, 10),
            new RecordFact('Amount', $record->amount, 20),
            new RecordFact('Date', $record->check_date?->format('Y-m-d') ?? 'N/A', 30),
            new RecordFact('Status', ucfirst(str_replace('_', ' ', $record->status)), 40),
        ];
    }

    public function previewFacts(Model $record): array
    {
        return $this->cardFacts($record);
    }

    public function scopeQuery(Builder $query): Builder
    {
        return $query;
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
