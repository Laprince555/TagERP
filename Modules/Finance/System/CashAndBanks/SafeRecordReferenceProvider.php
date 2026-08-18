<?php

namespace Modules\Finance\System\CashAndBanks;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Models\CashAndBanks\Safes\Safe;

class SafeRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'fin-cbn-saf';
    }

    public function modelClass(): string
    {
        return Safe::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'code', 'name', 'is_active'];
    }

    public function cardColumns(): array
    {
        return ['location', 'employee.display_name', 'entity.name'];
    }

    public function previewColumns(): array
    {
        return ['location', 'employee.display_name'];
    }

    public function title(Model $record): string
    {
        return (string) $record->name;
    }

    public function url(Model $record): ?string
    {
        return route('finance.cash-and-banks.safes.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        /** @var Safe $record */
        return [
            new RecordFact('Location', $record->location ?? 'N/A', 10),
            new RecordFact('Responsible', $record->employee?->display_name ?? 'Unassigned', 20),
            new RecordFact('Entity', $record->entity?->name ?? 'N/A', 30),
        ];
    }

    public function previewFacts(Model $record): array
    {
        return $this->cardFacts($record);
    }

    public function scopeQuery(Builder $query): Builder
    {
        return $query->where('is_active', true)->with(['employee', 'entity']);
    }

    public function authorize(Model $record): bool
    {
        /** @var Safe $record */
        return (bool) $record->is_active;
    }

    public function cacheTtl(): ?int
    {
        return null;
    }
}
