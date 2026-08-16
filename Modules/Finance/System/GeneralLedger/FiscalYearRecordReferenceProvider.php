<?php

namespace Modules\Finance\System\GeneralLedger;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Models\GeneralLedger\FiscalYear;

/**
 * Vertical-slice provider for the "fin-gl-fyr" Application.
 */
class FiscalYearRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'fin-gl-fyr';
    }

    public function modelClass(): string
    {
        return FiscalYear::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'name', 'code', 'is_active'];
    }

    public function cardColumns(): array
    {
        return ['start_date', 'end_date'];
    }

    public function previewColumns(): array
    {
        return ['start_date', 'end_date'];
    }

    public function title(Model $record): string
    {
        return (string) $record->name;
    }

    public function url(Model $record): ?string
    {
        return route('finance.general-ledger.fiscal-years.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        /** @var FiscalYear $record */
        return [
            new RecordFact('Start', $record->start_date->toDateString(), 10),
            new RecordFact('End', $record->end_date->toDateString(), 20),
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
