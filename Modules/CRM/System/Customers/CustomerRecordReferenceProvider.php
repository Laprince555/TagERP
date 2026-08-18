<?php

namespace Modules\CRM\System\Customers;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\CRM\Models\Customers\Customer;

/**
 * Vertical-slice provider for the "crm-cust-cus" Application.
 */
class CustomerRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return Customer::APPLICATION_CODE;
    }

    public function modelClass(): string
    {
        return Customer::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'code', 'company_id', 'customer_type', 'is_active'];
    }

    public function cardColumns(): array
    {
        return ['customer_type'];
    }

    public function previewColumns(): array
    {
        return ['customer_type'];
    }

    public function title(Model $record): string
    {
        /** @var Customer $record */
        return $record->company->name;
    }

    public function url(Model $record): ?string
    {
        return route('crm.customer-management.customers.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        /** @var Customer $record */
        return [
            new RecordFact('Type', $record->customer_type->label(), 10),
        ];
    }

    public function previewFacts(Model $record): array
    {
        return $this->cardFacts($record);
    }

    public function scopeQuery(Builder $query): Builder
    {
        return $query->where('is_active', true)->with('company');
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
