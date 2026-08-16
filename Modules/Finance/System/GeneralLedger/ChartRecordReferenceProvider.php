<?php

namespace Modules\Finance\System\GeneralLedger;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Models\GeneralLedger\Chart;

/**
 * Vertical-slice provider for the "fin-gl-coa" Application.
 */
class ChartRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'fin-gl-coa';
    }

    public function modelClass(): string
    {
        return Chart::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'name', 'code', 'is_active'];
    }

    public function cardColumns(): array
    {
        return ['levels_count'];
    }

    public function previewColumns(): array
    {
        return ['levels_count'];
    }

    public function title(Model $record): string
    {
        return (string) $record->name;
    }

    public function url(Model $record): ?string
    {
        return route('finance.general-ledger.charts.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        return [
            new RecordFact('Levels', (string) $record->levels_count, 10),
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
        /** @var Chart $record */
        return (bool) $record->is_active;
    }

    public function cacheTtl(): ?int
    {
        return null;
    }
}
