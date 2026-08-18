<?php

namespace Modules\Finance\System\AccountsPayable;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Models\AccountsPayable\ApInvoiceType;

/**
 * Vertical-slice provider for the "fin-ap-ivt" Application.
 */
class InvoiceTypeRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'fin-ap-ivt';
    }

    public function modelClass(): string
    {
        return ApInvoiceType::class;
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
        return route('finance.accounts-payable.invoice-types.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        /** @var ApInvoiceType $record */
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
