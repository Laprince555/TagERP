<?php

namespace Modules\Finance\System\Tax;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Models\Tax\TaxAdjustment;

/**
 * Vertical-slice provider for the "fin-tax-adj" Application.
 */
class TaxAdjustmentRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'fin-tax-adj';
    }

    public function modelClass(): string
    {
        return TaxAdjustment::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'code', 'amount', 'status'];
    }

    public function cardColumns(): array
    {
        return ['amount', 'status'];
    }

    public function previewColumns(): array
    {
        return ['amount'];
    }

    public function title(Model $record): string
    {
        return $record->code;
    }

    public function url(Model $record): ?string
    {
        return route('finance.tax-management.tax-adjustments.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        /** @var TaxAdjustment $record */
        return [
            new RecordFact('Amount', (string) $record->amount, 10),
            new RecordFact('Status', (string) $record->status, 20),
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
