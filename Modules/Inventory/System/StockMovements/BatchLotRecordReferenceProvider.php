<?php

namespace Modules\Inventory\System\StockMovements;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Models\BatchLot;

/**
 * Vertical-slice provider for the "inv-stk-bat" Application.
 */
class BatchLotRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return BatchLot::APPLICATION_CODE;
    }

    public function modelClass(): string
    {
        return BatchLot::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'batch_number', 'code', 'expiry_date', 'qty_available'];
    }

    public function cardColumns(): array
    {
        return ['batch_number', 'expiry_date'];
    }

    public function previewColumns(): array
    {
        return ['batch_number'];
    }

    public function title(Model $record): string
    {
        return $record->batch_number;
    }

    public function url(Model $record): ?string
    {
        return route('inventory.stock-movements.batch-lots.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        /** @var BatchLot $record */
        return [
            new RecordFact('Expiry', $record->expiry_date?->format('Y-m-d') ?? '-', 10),
            new RecordFact('Available', (string) $record->qty_available, 20),
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
