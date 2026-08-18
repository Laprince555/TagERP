<?php

namespace Modules\Finance\System\CashAndBanks;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Models\CashAndBanks\InternalAdjust\InternalAdjust;

class InternalAdjustRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'fin-cbn-iadj';
    }

    public function modelClass(): string
    {
        return InternalAdjust::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'code', 'number', 'status', 'is_active'];
    }

    public function cardColumns(): array
    {
        return ['amount', 'currency.code', 'adjustment_date', 'status'];
    }

    public function previewColumns(): array
    {
        return ['amount', 'status'];
    }

    public function title(Model $record): string
    {
        return sprintf('Transfer #%s', $record->number);
    }

    public function url(Model $record): ?string
    {
        return route('finance.cash-and-banks.internal-adjust.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        /** @var InternalAdjust $record */
        $from = $record->from_bank?->bank_name ?? $record->from_safe?->name ?? 'N/A';
        $to = $record->to_bank?->bank_name ?? $record->to_safe?->name ?? 'N/A';

        return [
            new RecordFact('From', $from, 10),
            new RecordFact('To', $to, 20),
            new RecordFact('Amount', $record->amount, 30),
            new RecordFact('Status', ucfirst($record->status), 40),
        ];
    }

    public function previewFacts(Model $record): array
    {
        return $this->cardFacts($record);
    }

    public function scopeQuery(Builder $query): Builder
    {
        return $query->with(['from_bank', 'from_safe', 'to_bank', 'to_safe', 'currency']);
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
