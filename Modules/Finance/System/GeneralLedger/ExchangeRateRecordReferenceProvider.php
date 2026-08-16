<?php

namespace Modules\Finance\System\GeneralLedger;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Models\GeneralLedger\ExchangeRate;

/**
 * Vertical-slice provider for the "fin-gl-rat" Application.
 */
class ExchangeRateRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'fin-gl-rat';
    }

    public function modelClass(): string
    {
        return ExchangeRate::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'code', 'rate', 'rate_date'];
    }

    public function cardColumns(): array
    {
        return ['rate_type'];
    }

    public function previewColumns(): array
    {
        return ['rate_type'];
    }

    public function title(Model $record): string
    {
        return (string) $record->rate;
    }

    public function url(Model $record): ?string
    {
        return route('finance.general-ledger.exchange-rates.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        /** @var ExchangeRate $record */
        return [
            new RecordFact('Date', $record->rate_date->toDateString(), 10),
            new RecordFact('Type', $record->rate_type->label(), 20),
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
