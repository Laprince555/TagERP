<?php

namespace Modules\Finance\System\CashAndBanks;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Models\CashAndBanks\Collection\CollectionRequest;

class CollectionRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'fin-cbn-col';
    }

    public function modelClass(): string
    {
        return CollectionRequest::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'code', 'number'];
    }

    public function cardColumns(): array
    {
        return ['number', 'expected_date', 'collection_date', 'amount', 'currency_id', 'collection_method', 'status'];
    }

    public function previewColumns(): array
    {
        return ['number', 'expected_date', 'amount', 'status'];
    }

    public function title(Model $record): string
    {
        return (string) $record->code;
    }

    public function url(Model $record): ?string
    {
        return route('finance.cash-and-banks.collection-requests.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        /** @var CollectionRequest $record */
        return [
            new RecordFact('Number', $record->number, 10),
            new RecordFact('Expected', $record->expected_date?->format('Y-m-d'), 20),
            new RecordFact('Collected', $record->collection_date?->format('Y-m-d'), 30),
            new RecordFact('Amount', $record->amount, 40),
            new RecordFact('Method', ucfirst(str_replace('_', ' ', $record->collection_method)), 50),
            new RecordFact('Status', ucfirst($record->status), 60),
        ];
    }

    public function previewFacts(Model $record): array
    {
        return $this->cardFacts($record);
    }

    public function scopeQuery(Builder $query): Builder
    {
        return $query->latest();
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
