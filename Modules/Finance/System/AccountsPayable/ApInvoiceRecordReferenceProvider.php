<?php

namespace Modules\Finance\System\AccountsPayable;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Models\AccountsPayable\ApInvoice;

/**
 * Vertical-slice provider for the "fin-ap-inv" Application.
 */
class ApInvoiceRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return ApInvoice::APPLICATION_CODE;
    }

    public function modelClass(): string
    {
        return ApInvoice::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'code', 'invoice_number', 'status', 'total_amount'];
    }

    public function cardColumns(): array
    {
        return ['invoice_number', 'status'];
    }

    public function previewColumns(): array
    {
        return ['invoice_number', 'status', 'total_amount'];
    }

    public function title(Model $record): string
    {
        /** @var ApInvoice $record */
        return $record->invoice_number;
    }

    public function url(Model $record): ?string
    {
        return route('finance.accounts-payable.invoices.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        /** @var ApInvoice $record */
        return [
            new RecordFact('Status', $record->status->label(), 10),
            new RecordFact('Total', (string) $record->total_amount, 20),
        ];
    }

    public function previewFacts(Model $record): array
    {
        return $this->cardFacts($record);
    }

    public function scopeQuery(Builder $query): Builder
    {
        return $query;
    }

    public function authorize(Model $record): bool
    {
        return true;
    }

    public function cacheTtl(): ?int
    {
        return null;
    }
}
