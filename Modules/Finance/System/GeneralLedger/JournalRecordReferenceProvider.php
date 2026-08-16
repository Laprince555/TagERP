<?php

namespace Modules\Finance\System\GeneralLedger;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Models\GeneralLedger\Journal;

/**
 * Vertical-slice provider for the "fin-gl-jou" Application.
 */
class JournalRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'fin-gl-jou';
    }

    public function modelClass(): string
    {
        return Journal::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'code', 'number', 'status'];
    }

    public function cardColumns(): array
    {
        return ['journal_date', 'total_debit'];
    }

    public function previewColumns(): array
    {
        return ['journal_date'];
    }

    public function title(Model $record): string
    {
        return (string) ($record->number ?? $record->code);
    }

    public function url(Model $record): ?string
    {
        return route('finance.general-ledger.journals.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        /** @var Journal $record */
        return [
            new RecordFact('Date', $record->journal_date->toDateString(), 10),
            new RecordFact('Status', $record->status->label(), 20),
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
