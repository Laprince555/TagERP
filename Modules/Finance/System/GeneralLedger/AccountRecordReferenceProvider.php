<?php

namespace Modules\Finance\System\GeneralLedger;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\Finance\Services\GeneralLedger\AccountAccessResolver;

/**
 * Vertical-slice provider for the "fin-gl-acc" Application.
 */
class AccountRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'fin-gl-acc';
    }

    public function modelClass(): string
    {
        return Account::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'name', 'code', 'number', 'is_active'];
    }

    public function cardColumns(): array
    {
        return ['number', 'parent_id', 'category_id'];
    }

    public function previewColumns(): array
    {
        return ['number'];
    }

    public function title(Model $record): string
    {
        return $record->number.' — '.$record->name;
    }

    public function url(Model $record): ?string
    {
        return route('finance.general-ledger.accounts.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        /** @var Account $record */
        return array_values(array_filter([
            new RecordFact('Number', (string) $record->number, 10),
            $record->category ? new RecordFact('Category', (string) $record->category->name, 20) : null,
        ]));
    }

    public function previewFacts(Model $record): array
    {
        return $this->cardFacts($record);
    }

    /**
     * Every account picker, tag and preview in the application resolves through
     * here, so restricting it once covers all of them.
     */
    public function scopeQuery(Builder $query): Builder
    {
        return app(AccountAccessResolver::class)->restrict(
            $query->where('is_active', true)
        );
    }

    public function authorize(Model $record): bool
    {
        /** @var Account $record */
        return (bool) $record->is_active;
    }

    public function cacheTtl(): ?int
    {
        return null;
    }
}
