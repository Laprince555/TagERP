<?php

namespace Modules\Inventory\System\Warehousing;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Models\Warehouse;

/**
 * Vertical-slice provider for the "inv-whs-wrh" Application.
 */
class WarehouseRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return Warehouse::APPLICATION_CODE;
    }

    public function modelClass(): string
    {
        return Warehouse::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'name', 'code'];
    }

    public function cardColumns(): array
    {
        return ['code', 'capacity_m3'];
    }

    public function previewColumns(): array
    {
        return ['code', 'capacity_m3'];
    }

    public function title(Model $record): string
    {
        return $record->name;
    }

    public function url(Model $record): ?string
    {
        return route('inventory.warehousing.warehouses.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        /** @var Warehouse $record */
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
        return $query->whereNull('deleted_at');
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
