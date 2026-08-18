<?php

namespace Modules\Finance\System\CashAndBanks;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Models\CashAndBanks\Banks\ChecksBook;

class ChecksBookRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'fin-cbn-bnk-cbk';
    }

    public function modelClass(): string
    {
        return ChecksBook::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'code', 'is_active'];
    }

    public function cardColumns(): array
    {
        return ['check_series_start', 'check_series_end', 'current_check_number', 'status'];
    }

    public function previewColumns(): array
    {
        return ['check_series_start', 'check_series_end', 'status'];
    }

    public function title(Model $record): string
    {
        return (string) $record->code;
    }

    public function url(Model $record): ?string
    {
        $bankId = $record->bank_id;

        return route('finance.cash-and-banks.banks.checks-books.show', ['bankId' => $bankId, 'recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        /** @var ChecksBook $record */
        return [
            new RecordFact('Start Number', $record->check_series_start, 10),
            new RecordFact('End Number', $record->check_series_end, 20),
            new RecordFact('Current Number', $record->current_check_number, 30),
            new RecordFact('Status', ucfirst($record->status), 40),
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
        /** @var ChecksBook $record */
        return (bool) $record->is_active;
    }

    public function cacheTtl(): ?int
    {
        return null;
    }
}
