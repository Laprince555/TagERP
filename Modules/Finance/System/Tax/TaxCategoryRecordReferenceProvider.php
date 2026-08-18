<?php

namespace Modules\Finance\System\Tax;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Models\Tax\TaxCategory;

/**
 * Vertical-slice provider for the "fin-tax-cat" Application.
 */
class TaxCategoryRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'fin-tax-cat';
    }

    public function modelClass(): string
    {
        return TaxCategory::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'name', 'code', 'is_active'];
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
        return $record->name;
    }

    public function url(Model $record): ?string
    {
        return route('finance.tax-management.tax-categories.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        /** @var TaxCategory $record */
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
