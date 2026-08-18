<?php

namespace Modules\Inventory\System\StockMovements;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Models\Allocation;

/**
 * Vertical-slice provider for the "inv-stk-alc" Application.
 */
class AllocationRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return Allocation::APPLICATION_CODE;
    }

    public function modelClass(): string
    {
        return Allocation::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'code', 'status', 'allocated_qty'];
    }

    public function cardColumns(): array
    {
        return ['status', 'allocated_qty'];
    }

    public function previewColumns(): array
    {
        return ['status'];
    }

    public function title(Model $record): string
    {
        return $record->code;
    }

    public function url(Model $record): ?string
    {
        return route('inventory.stock-movements.allocations.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        /** @var Allocation $record */
        return [
            new RecordFact('Status', ucfirst($record->status), 10),
            new RecordFact('Allocated', (string) $record->allocated_qty, 20),
        ];
    }

    public function previewFacts(Model $record): array
    {
        return $this->cardFacts($record);
    }

    public function scopeQuery(Builder $query): Builder
    {
        return $query->whereIn('status', ['pending', 'fulfilled']);
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
