<?php

namespace Modules\Inventory\System\StockMovements;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Models\InventoryMovement;

/**
 * Vertical-slice provider for the "inv-stk-mov" Application.
 */
class InventoryMovementRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return InventoryMovement::APPLICATION_CODE;
    }

    public function modelClass(): string
    {
        return InventoryMovement::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'code', 'movement_type', 'quantity'];
    }

    public function cardColumns(): array
    {
        return ['movement_type', 'quantity'];
    }

    public function previewColumns(): array
    {
        return ['movement_type', 'quantity'];
    }

    public function title(Model $record): string
    {
        /** @var InventoryMovement $record */
        return $record->code.' — '.$record->movement_type.' ('.$record->quantity.')';
    }

    public function url(Model $record): ?string
    {
        return route('inventory.stock-movements.movements.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        /** @var InventoryMovement $record */
        return [
            new RecordFact('Type', (string) $record->movement_type, 10),
            new RecordFact('Quantity', (string) $record->quantity, 20),
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
