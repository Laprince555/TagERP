<?php

namespace Modules\Finance\System\Tax;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Models\Tax\TaxGlLink;

/**
 * Vertical-slice provider for the "fin-tax-gll" Application.
 */
class TaxGlLinkRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'fin-tax-gll';
    }

    public function modelClass(): string
    {
        return TaxGlLink::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'code', 'is_active'];
    }

    public function cardColumns(): array
    {
        return ['code'];
    }

    public function previewColumns(): array
    {
        return ['code'];
    }

    public function title(Model $record): string
    {
        return $record->code;
    }

    public function url(Model $record): ?string
    {
        return route('finance.tax-management.tax-gl-links.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        /** @var TaxGlLink $record */
        return [
            new RecordFact('Code', (string) $record->code, 10),
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
