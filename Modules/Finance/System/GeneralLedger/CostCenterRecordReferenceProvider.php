<?php

namespace Modules\Finance\System\GeneralLedger;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Models\GeneralLedger\CostCenter;

/**
 * Vertical-slice provider for the "fin-gl-cct" Application.
 */
class CostCenterRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'fin-gl-cct';
    }

    public function modelClass(): string
    {
        return CostCenter::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'name', 'code', 'number', 'is_active'];
    }

    public function cardColumns(): array
    {
        return ['number'];
    }

    public function previewColumns(): array
    {
        return ['number'];
    }

    public function title(Model $record): string
    {
        return $record->number.' - '.$record->name;
    }

    public function url(Model $record): ?string
    {
        return route('finance.general-ledger.cost-centers.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        /** @var CostCenter $record */
        return [
            new RecordFact('Number', (string) $record->number, 10),
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
