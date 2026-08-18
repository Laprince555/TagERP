<?php

namespace Modules\Finance\System\AccountsReceivable;

use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Models\AccountsReceivable\CustomerCategory;

/**
 * Vertical-slice provider for the "fin-ar-cct" Application.
 */
class CustomerCategoryRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return CustomerCategory::APPLICATION_CODE;
    }

    public function modelClass(): string
    {
        return CustomerCategory::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'code', 'name', 'is_active'];
    }

    public function cardColumns(): array
    {
        return [];
    }

    public function previewColumns(): array
    {
        return [];
    }

    public function title(Model $record): string
    {
        /** @var CustomerCategory $record */
        return $record->name;
    }

    public function url(Model $record): ?string
    {
        return route('finance.accounts-receivable.customer-categories.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        return [];
    }

    public function previewFacts(Model $record): array
    {
        return [];
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
