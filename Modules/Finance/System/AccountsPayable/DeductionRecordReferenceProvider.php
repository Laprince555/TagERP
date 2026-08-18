<?php

namespace Modules\Finance\System\AccountsPayable;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Models\AccountsPayable\Deduction;

/**
 * Vertical-slice provider for the "fin-ap-ddc" Application.
 */
class DeductionRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'fin-ap-ddc';
    }

    public function modelClass(): string
    {
        return Deduction::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'name', 'code', 'value', 'is_active'];
    }

    public function cardColumns(): array
    {
        return ['value'];
    }

    public function previewColumns(): array
    {
        return ['value'];
    }

    public function title(Model $record): string
    {
        return $record->name;
    }

    public function url(Model $record): ?string
    {
        return route('finance.accounts-payable.deductions.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        /** @var Deduction $record */
        return [
            new RecordFact('Value', (string) $record->value, 10),
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
