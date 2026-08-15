<?php

namespace Modules\General\System\World;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\General\Models\World\Currency;

/**
 * Vertical-slice provider for the "gen-wld-cur" Application. Owns every
 * record-reference concern for Currency: title, route, facts, and scoping.
 */
class CurrencyRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'gen-wld-cur';
    }

    public function modelClass(): string
    {
        return Currency::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'name', 'code'];
    }

    public function cardColumns(): array
    {
        return ['symbol', 'precision'];
    }

    public function previewColumns(): array
    {
        return ['symbol', 'symbol_native', 'precision'];
    }

    public function title(Model $record): string
    {
        return (string) $record->name;
    }

    public function url(Model $record): ?string
    {
        return route('general.world.currencies.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        return array_values(array_filter([
            $record->symbol ? new RecordFact(__('messages.record_reference.facts.symbol'), $record->symbol, 10) : null,
        ]));
    }

    public function previewFacts(Model $record): array
    {
        return array_merge($this->cardFacts($record), array_values(array_filter([
            $record->precision !== null ? new RecordFact(__('messages.record_reference.facts.precision'), (string) $record->precision, 9) : null,
        ])));
    }

    public function scopeQuery(Builder $query): Builder
    {
        return $query;
    }

    /**
     * Currencies carry no active/inactive flag — every seeded Currency is
     * referenceable once the Application-level check has passed.
     */
    public function authorize(Model $record): bool
    {
        return true;
    }

    public function cacheTtl(): ?int
    {
        return null;
    }
}
