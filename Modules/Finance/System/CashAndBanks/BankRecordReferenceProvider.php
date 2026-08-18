<?php

namespace Modules\Finance\System\CashAndBanks;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Models\CashAndBanks\Banks\Bank;

class BankRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'fin-cbn-bnk';
    }

    public function modelClass(): string
    {
        return Bank::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'code', 'bank_name', 'is_active'];
    }

    public function cardColumns(): array
    {
        return ['bank_code', 'swift_code', 'entity.name'];
    }

    public function previewColumns(): array
    {
        return ['bank_code', 'swift_code'];
    }

    public function title(Model $record): string
    {
        return (string) $record->bank_name;
    }

    public function url(Model $record): ?string
    {
        return route('finance.cash-and-banks.banks.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        /** @var Bank $record */
        return [
            new RecordFact('Bank Code', $record->bank_code ?? 'N/A', 10),
            new RecordFact('SWIFT Code', $record->swift_code ?? 'N/A', 20),
            new RecordFact('Entity', $record->entity?->name ?? 'N/A', 30),
        ];
    }

    public function previewFacts(Model $record): array
    {
        return $this->cardFacts($record);
    }

    public function scopeQuery(Builder $query): Builder
    {
        return $query->where('is_active', true)->with('entity');
    }

    public function authorize(Model $record): bool
    {
        /** @var Bank $record */
        return (bool) $record->is_active;
    }

    public function cacheTtl(): ?int
    {
        return null;
    }
}
