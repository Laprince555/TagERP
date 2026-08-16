<?php

namespace Modules\Finance\System\GeneralLedger;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Models\GeneralLedger\JournalBook;

/**
 * Vertical-slice provider for the "fin-gl-bok" Application.
 */
class JournalBookRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'fin-gl-bok';
    }

    public function modelClass(): string
    {
        return JournalBook::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'name', 'code', 'is_active'];
    }

    public function cardColumns(): array
    {
        return ['sequence_prefix'];
    }

    public function previewColumns(): array
    {
        return ['sequence_prefix'];
    }

    public function title(Model $record): string
    {
        return (string) $record->name;
    }

    public function url(Model $record): ?string
    {
        return route('finance.general-ledger.journal-books.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        /** @var JournalBook $record */
        return [
            new RecordFact('Prefix', (string) $record->sequence_prefix, 10),
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
