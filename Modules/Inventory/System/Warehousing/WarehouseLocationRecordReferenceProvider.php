<?php

namespace Modules\Inventory\System\Warehousing;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Models\WarehouseLocation;

/**
 * Vertical-slice provider for the "inv-whs-wrh-loc" SubApplication. Locations
 * have no standalone route (embedded-only, same as ApInvoiceLine), so url()
 * returns null.
 */
class WarehouseLocationRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'inv-whs-wrh-loc';
    }

    public function modelClass(): string
    {
        return WarehouseLocation::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'code', 'aisle', 'shelf', 'bin'];
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
        return (string) $record->code;
    }

    public function url(Model $record): ?string
    {
        return null;
    }

    public function cardFacts(Model $record): array
    {
        /** @var WarehouseLocation $record */
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
