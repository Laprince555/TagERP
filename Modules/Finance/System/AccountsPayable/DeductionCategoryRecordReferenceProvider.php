<?php

namespace Modules\Finance\System\AccountsPayable;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Models\AccountsPayable\DeductionCategory;

/**
 * Vertical-slice provider for the "fin-ap-dcc" Application.
 */
class DeductionCategoryRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'fin-ap-dcc';
    }

    public function modelClass(): string
    {
        return DeductionCategory::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'name', 'code', 'is_active'];
    }

    public function cardColumns(): array
    {
        return ['code'];
    }

    public function previewColumns(): array
    {
        return ['code'];
    }

    public function title(Model $record): string
    {
        return $record->name;
    }

    public function url(Model $record): ?string
    {
        return route('finance.accounts-payable.deduction-categories.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        /** @var DeductionCategory $record */
        return [
            new RecordFact('Code', (string) $record->code, 10),
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
