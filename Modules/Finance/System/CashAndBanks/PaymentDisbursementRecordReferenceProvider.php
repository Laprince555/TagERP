<?php

namespace Modules\Finance\System\CashAndBanks;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Models\CashAndBanks\PaymentDisburse\PaymentDisbursementRequest;

class PaymentDisbursementRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'fin-cbn-pdr';
    }

    public function modelClass(): string
    {
        return PaymentDisbursementRequest::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'code', 'number'];
    }

    public function cardColumns(): array
    {
        return ['number', 'payment_date', 'amount', 'currency_id', 'payment_method', 'status'];
    }

    public function previewColumns(): array
    {
        return ['number', 'payment_date', 'amount', 'status'];
    }

    public function title(Model $record): string
    {
        return (string) $record->code;
    }

    public function url(Model $record): ?string
    {
        return route('finance.cash-and-banks.payment-disbursements.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        /** @var PaymentDisbursementRequest $record */
        return [
            new RecordFact('Number', $record->number, 10),
            new RecordFact('Date', $record->payment_date?->format('Y-m-d'), 20),
            new RecordFact('Amount', $record->amount, 30),
            new RecordFact('Method', ucfirst(str_replace('_', ' ', $record->payment_method)), 40),
            new RecordFact('Status', ucfirst($record->status), 50),
        ];
    }

    public function previewFacts(Model $record): array
    {
        return $this->cardFacts($record);
    }

    public function scopeQuery(Builder $query): Builder
    {
        return $query->latest();
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
