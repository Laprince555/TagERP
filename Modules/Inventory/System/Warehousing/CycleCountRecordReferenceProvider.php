<?php

namespace Modules\Inventory\System\Warehousing;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Models\CycleCount;

/**
 * Vertical-slice provider for the "inv-whs-ccn" Application.
 */
class CycleCountRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return CycleCount::APPLICATION_CODE;
    }

    public function modelClass(): string
    {
        return CycleCount::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'code', 'status'];
    }

    public function cardColumns(): array
    {
        return ['count_date', 'status'];
    }

    public function previewColumns(): array
    {
        return ['count_date'];
    }

    public function title(Model $record): string
    {
        return (string) $record->code;
    }

    public function url(Model $record): ?string
    {
        return route('inventory.warehousing.cycle-counts.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        /** @var CycleCount $record */
        return [
            new RecordFact('Date', $record->count_date->toDateString(), 10),
            new RecordFact('Status', CycleCountStatus::from($record->status)->label(), 20),
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
