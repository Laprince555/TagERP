<?php

namespace Modules\Finance\System\Tax;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Models\Tax\Tax;

/**
 * Vertical-slice provider for the "fin-tax-tax" Application.
 */
class TaxRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'fin-tax-tax';
    }

    public function modelClass(): string
    {
        return Tax::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'name', 'code', 'rate', 'is_active'];
    }

    public function cardColumns(): array
    {
        return ['rate'];
    }

    public function previewColumns(): array
    {
        return ['rate'];
    }

    public function title(Model $record): string
    {
        return $record->name;
    }

    public function url(Model $record): ?string
    {
        return route('finance.tax-management.taxes.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        /** @var Tax $record */
        return [
            new RecordFact('Rate', $record->rate.'%', 10),
        ];
    }

    public function previewFacts(Model $record): array
    {
        return $this->cardFacts($record);
    }

    public function scopeQuery(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function authorize(Model $record): bool
    {
        return (bool) $record->is_active;
    }

    public function cacheTtl(): ?int
    {
        return null;
    }
}
