<?php

namespace Modules\Finance\System\GeneralLedger;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Models\GeneralLedger\AccountGroup;

/**
 * Vertical-slice provider for the "fin-gl-agr" Application.
 */
class AccountGroupRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'fin-gl-agr';
    }

    public function modelClass(): string
    {
        return AccountGroup::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'name', 'code', 'is_active'];
    }

    public function cardColumns(): array
    {
        return ['purpose'];
    }

    public function previewColumns(): array
    {
        return ['purpose'];
    }

    public function title(Model $record): string
    {
        return (string) $record->name;
    }

    public function url(Model $record): ?string
    {
        return route('finance.general-ledger.account-groups.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        /** @var AccountGroup $record */
        return [
            new RecordFact('Purpose', $record->purpose->label(), 10),
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
