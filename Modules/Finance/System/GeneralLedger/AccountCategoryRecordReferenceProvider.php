<?php

namespace Modules\Finance\System\GeneralLedger;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Models\GeneralLedger\AccountCategory;

/**
 * Vertical-slice provider for the "fin-gl-cat" Application.
 */
class AccountCategoryRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'fin-gl-cat';
    }

    public function modelClass(): string
    {
        return AccountCategory::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'name', 'code', 'is_active'];
    }

    public function cardColumns(): array
    {
        return ['nature'];
    }

    public function previewColumns(): array
    {
        return ['nature'];
    }

    public function title(Model $record): string
    {
        return (string) $record->name;
    }

    public function url(Model $record): ?string
    {
        return route('finance.general-ledger.account-categories.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        /** @var AccountCategory $record */
        return [
            new RecordFact('Nature', $record->nature->label(), 10),
            new RecordFact('Normal Balance', $record->nature->normalBalance()->label(), 20),
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
        /** @var AccountCategory $record */
        return (bool) $record->is_active;
    }

    public function cacheTtl(): ?int
    {
        return null;
    }
}
